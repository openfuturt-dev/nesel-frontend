<?php

namespace Tests\Feature\Console;

use App\Actions\SendContactRequestNotification;
use App\Enums\DeliveryStatus;
use App\Models\ContactRequest;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\InteractsWithMailTransport;
use Tests\TestCase;

class DispatchContactRequestNotificationsTest extends TestCase
{
    use InteractsWithMailTransport, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeSecond();
    }

    public function test_it_recovers_requests_whose_deferred_attempt_never_ran(): void
    {
        Mail::fake();
        $missed = ContactRequest::factory()->create();

        $this->artisan('contact-requests:dispatch')
            ->expectsOutputToContain('Delivered: 1. Retry scheduled: 0. Failed: 0. Skipped: 0.')
            ->assertSuccessful();

        Mail::assertSentCount(1);
        $this->assertSame(DeliveryStatus::Delivered, $missed->fresh()->notification_status);
    }

    public function test_it_only_sends_requests_that_are_due(): void
    {
        Mail::fake();
        $due = ContactRequest::factory()->create(['notification_attempts' => 1, 'notification_next_attempt_at' => now()]);
        ContactRequest::factory()->create(['notification_attempts' => 1, 'notification_next_attempt_at' => now()->addMinute()]);
        ContactRequest::factory()->create(['notification_status' => DeliveryStatus::Delivered, 'notification_attempts' => 1]);
        ContactRequest::factory()->create(['notification_status' => DeliveryStatus::Failed, 'notification_attempts' => 6]);

        $this->artisan('contact-requests:dispatch')->assertSuccessful();

        Mail::assertSentCount(1);
        $this->assertSame(DeliveryStatus::Delivered, $due->fresh()->notification_status);
    }

    public function test_each_run_processes_a_bounded_batch_oldest_first(): void
    {
        Mail::fake();
        $requests = ContactRequest::factory()->count(3)->create();

        $this->artisan('contact-requests:dispatch', ['--limit' => 2])->assertSuccessful();

        Mail::assertSentCount(2);
        $this->assertSame(
            [DeliveryStatus::Delivered, DeliveryStatus::Delivered, DeliveryStatus::Pending],
            $requests->map(fn (ContactRequest $contactRequest) => $contactRequest->fresh()->notification_status)->all(),
        );
    }

    public function test_repeated_runs_send_each_request_once(): void
    {
        Mail::fake();
        ContactRequest::factory()->count(2)->create();

        $this->artisan('contact-requests:dispatch')->assertSuccessful();
        $this->artisan('contact-requests:dispatch')->assertSuccessful();

        Mail::assertSentCount(2);
    }

    public function test_a_concurrent_run_skips_requests_already_being_sent(): void
    {
        ContactRequest::factory()->create();
        $sends = 0;

        $this->useMailTransport(function () use (&$sends): void {
            $sends++;
            // Another scheduler run starts while this send is still in progress.
            Artisan::call('contact-requests:dispatch');
        });

        $this->artisan('contact-requests:dispatch')->assertSuccessful();

        $this->assertSame(1, $sends);
        $this->assertSame(1, ContactRequest::sole()->notification_attempts);
    }

    public function test_it_fails_requests_abandoned_on_their_last_attempt(): void
    {
        $abandoned = ContactRequest::factory()->create([
            'notification_attempts' => SendContactRequestNotification::MAX_ATTEMPTS,
            'notification_claim_token' => 'a7f1c0de-0000-4000-8000-000000000000',
            'notification_claimed_until' => now()->subSecond(),
        ]);

        $this->artisan('contact-requests:dispatch')
            ->expectsOutputToContain('Failed: 1.')
            ->assertSuccessful();

        $this->assertSame(DeliveryStatus::Failed, $abandoned->fresh()->notification_status);
    }

    public function test_it_is_scheduled_every_minute_without_overlapping(): void
    {
        $event = $this->scheduledEvent('contact-requests:dispatch');

        $this->assertSame('* * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertSame(10, $event->expiresAt);
        $this->assertSame('0 * * * *', $this->scheduledEvent('contact-requests:monitor')->expression);
    }

    private function scheduledEvent(string $command): Event
    {
        Artisan::all(); // Loads routes/console.php, where the schedule is defined.

        return collect($this->app->make(Schedule::class)->events())
            ->sole(fn (Event $event): bool => str_contains((string) $event->command, $command));
    }
}
