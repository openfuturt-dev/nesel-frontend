<?php

namespace Tests\Feature\Actions;

use App\Actions\SendContactRequestNotification;
use App\Enums\DeliveryStatus;
use App\Mail\ContactRequestSubmitted;
use App\Models\ContactRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\Concerns\InteractsWithMailTransport;
use Tests\TestCase;

class SendContactRequestNotificationTest extends TestCase
{
    use InteractsWithMailTransport, RefreshDatabase;

    private SendContactRequestNotification $sendNotification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeSecond();
        $this->sendNotification = $this->app->make(SendContactRequestNotification::class);
    }

    public function test_delivery_marks_the_request_delivered_and_releases_the_claim(): void
    {
        Mail::fake();
        $contactRequest = ContactRequest::factory()->create();

        $this->assertSame(DeliveryStatus::Delivered, $this->sendNotification->handle($contactRequest));

        Mail::assertSent(ContactRequestSubmitted::class, fn (ContactRequestSubmitted $mail): bool => $mail->reference === $contactRequest->public_id
            && $mail->email === $contactRequest->email);
        $contactRequest->refresh();
        $this->assertSame(DeliveryStatus::Delivered, $contactRequest->notification_status);
        $this->assertSame(1, $contactRequest->notification_attempts);
        $this->assertTrue($contactRequest->notification_sent_at->equalTo(now()));
        $this->assertNull($contactRequest->notification_claim_token);
        $this->assertNull($contactRequest->notification_claimed_until);
        $this->assertNull($contactRequest->notification_next_attempt_at);
        $this->assertSame(DeliveryStatus::Pending, $contactRequest->crm_status);
    }

    public function test_smtp_failure_schedules_a_retry_and_logs_no_personal_data(): void
    {
        Log::spy();
        $this->useFailingMailer();
        $contactRequest = ContactRequest::factory()->create(['name' => 'Nassim Namous', 'email' => 'nassim@example.com']);

        $this->assertSame(DeliveryStatus::Pending, $this->sendNotification->handle($contactRequest));

        $contactRequest->refresh();
        $this->assertSame(1, $contactRequest->notification_attempts);
        $this->assertSame('smtp_unavailable', $contactRequest->notification_last_error);
        $this->assertTrue($contactRequest->notification_next_attempt_at->equalTo(now()->addMinute()));
        $this->assertNull($contactRequest->notification_claim_token);
        $this->assertNull($contactRequest->notification_sent_at);

        Log::shouldHaveReceived('warning')->once()->with(
            'Contact request notification failed; retry scheduled.',
            Mockery::on(function (array $context) use ($contactRequest): bool {
                $this->assertSame([
                    'contact_request_id' => $contactRequest->public_id,
                    'attempt' => 1,
                    'error_code' => 'smtp_unavailable',
                    'exception' => 'TransportException',
                    'next_attempt_at' => now()->addMinute()->toIso8601String(),
                    'claim_lost' => false,
                ], $context);

                return true;
            }),
        );
    }

    public function test_retries_follow_the_backoff_schedule_then_fail_permanently(): void
    {
        Log::spy();
        $this->useFailingMailer();
        $contactRequest = ContactRequest::factory()->create();

        foreach ([60, 300, 900, 3600, 21600] as $attempt => $delay) {
            $this->assertSame(DeliveryStatus::Pending, $this->sendNotification->handle($contactRequest));
            $this->assertSame($attempt + 1, $contactRequest->notification_attempts);
            $this->assertTrue($contactRequest->notification_next_attempt_at->equalTo(now()->addSeconds($delay)));

            $this->travel($delay - 1)->seconds();
            $this->assertNull($this->sendNotification->handle($contactRequest), "Attempted before the {$delay}s delay elapsed.");
            $this->travel(1)->seconds();
        }

        $this->assertSame(DeliveryStatus::Failed, $this->sendNotification->handle($contactRequest));
        $this->assertSame(SendContactRequestNotification::MAX_ATTEMPTS, $contactRequest->notification_attempts);
        $this->assertNull($contactRequest->notification_next_attempt_at);

        $this->travel(1)->day();
        $this->assertNull($this->sendNotification->handle($contactRequest));
        $this->assertSame(SendContactRequestNotification::MAX_ATTEMPTS, $contactRequest->fresh()->notification_attempts);
        Log::shouldHaveReceived('error')->once();
    }

    public function test_smtp_rejections_are_retried_with_their_reply_code(): void
    {
        $this->useFailingMailer(new TransportException('Expected response code "235" but got code "535".', 535));
        $contactRequest = ContactRequest::factory()->create();

        $this->assertSame(DeliveryStatus::Pending, $this->sendNotification->handle($contactRequest));
        $this->assertSame('smtp_535', $contactRequest->notification_last_error);
    }

    public function test_an_unusable_stored_address_fails_permanently_without_retry(): void
    {
        Log::spy();
        $this->useMailTransport(fn () => $this->fail('A message with an invalid Reply-To must not reach SMTP.'));
        $contactRequest = ContactRequest::factory()->create(['email' => 'nassim(comment)@example.com']);

        $this->assertSame(DeliveryStatus::Failed, $this->sendNotification->handle($contactRequest));

        $this->assertSame('invalid_address', $contactRequest->notification_last_error);
        $this->assertNull($contactRequest->notification_next_attempt_at);
        Log::shouldHaveReceived('error')->once()->with(
            'Contact request notification failed permanently.',
            Mockery::on(fn (array $context): bool => ! str_contains(json_encode($context), 'nassim')),
        );
    }

    public function test_a_failed_attempt_recovers_on_the_next_retry(): void
    {
        $this->useFailingMailer();
        $contactRequest = ContactRequest::factory()->create();
        $this->sendNotification->handle($contactRequest);

        Mail::fake();
        $this->travel(1)->minute();

        $this->assertSame(DeliveryStatus::Delivered, $this->sendNotification->handle($contactRequest));
        Mail::assertSentCount(1);
        $this->assertSame(2, $contactRequest->notification_attempts);
        $this->assertNull($contactRequest->notification_last_error);
    }

    public function test_a_request_being_sent_cannot_be_claimed_by_another_process(): void
    {
        $contactRequest = ContactRequest::factory()->create();
        $sends = 0;
        $concurrentOutcome = 'not attempted';

        $this->useMailTransport(function () use ($contactRequest, &$sends, &$concurrentOutcome): void {
            $sends++;
            // A second process picks up the same row while the first is mid-send.
            $concurrentOutcome = $this->app->make(SendContactRequestNotification::class)
                ->handle(ContactRequest::findOrFail($contactRequest->id));
        });

        $this->assertSame(DeliveryStatus::Delivered, $this->sendNotification->handle($contactRequest));
        $this->assertNull($concurrentOutcome);
        $this->assertSame(1, $sends);
        $this->assertSame(1, $contactRequest->notification_attempts);
    }

    public function test_an_expired_claim_is_recovered(): void
    {
        Mail::fake();
        $contactRequest = ContactRequest::factory()->create([
            'notification_attempts' => 1,
            'notification_claim_token' => 'a7f1c0de-0000-4000-8000-000000000000',
            'notification_claimed_until' => now()->subSecond(),
        ]);

        $this->assertSame(DeliveryStatus::Delivered, $this->sendNotification->handle($contactRequest));
        $this->assertSame(2, $contactRequest->notification_attempts);
        Mail::assertSentCount(1);
    }

    public function test_a_live_claim_is_left_alone(): void
    {
        Mail::fake();
        $contactRequest = ContactRequest::factory()->create([
            'notification_attempts' => 1,
            'notification_claim_token' => 'a7f1c0de-0000-4000-8000-000000000000',
            'notification_claimed_until' => now()->addMinute(),
        ]);

        $this->assertNull($this->sendNotification->handle($contactRequest));
        Mail::assertNothingSent();
    }

    public function test_delivered_and_failed_requests_are_never_sent_again(): void
    {
        Mail::fake();
        $delivered = ContactRequest::factory()->create(['notification_status' => DeliveryStatus::Delivered, 'notification_attempts' => 1]);
        $failed = ContactRequest::factory()->create(['notification_status' => DeliveryStatus::Failed, 'notification_attempts' => 1]);

        $this->assertNull($this->sendNotification->handle($delivered));
        $this->assertNull($this->sendNotification->handle($failed));
        Mail::assertNothingSent();
    }

    public function test_requests_abandoned_on_their_last_attempt_are_failed_once_the_claim_expires(): void
    {
        Log::spy();
        $abandoned = ContactRequest::factory()->create([
            'notification_attempts' => SendContactRequestNotification::MAX_ATTEMPTS,
            'notification_claim_token' => 'a7f1c0de-0000-4000-8000-000000000000',
            'notification_claimed_until' => now()->subSecond(),
        ]);
        $inFlight = ContactRequest::factory()->create([
            'notification_attempts' => SendContactRequestNotification::MAX_ATTEMPTS,
            'notification_claim_token' => 'b7f1c0de-0000-4000-8000-000000000000',
            'notification_claimed_until' => now()->addMinute(),
        ]);

        $this->assertSame(1, $this->sendNotification->failAbandoned());

        $this->assertSame(DeliveryStatus::Failed, $abandoned->fresh()->notification_status);
        $this->assertSame('claim_expired', $abandoned->fresh()->notification_last_error);
        $this->assertSame(DeliveryStatus::Pending, $inFlight->fresh()->notification_status);
        Log::shouldHaveReceived('error')->once();
    }
}
