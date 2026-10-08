<?php

namespace Tests\Feature\Crm;

use App\Actions\SyncContactRequestToCrm;
use App\Enums\CrmSyncOutcome;
use App\Enums\DeliveryStatus;
use App\Models\ContactRequest;
use App\Support\StartEntreprise\LeadPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\FakesStartEntreprise;
use Tests\TestCase;

class SyncContactRequestToCrmTest extends TestCase
{
    use FakesStartEntreprise, RefreshDatabase;

    private SyncContactRequestToCrm $sync;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeSecond();
        $this->enableStartEntreprise();
        $this->sync = $this->app->make(SyncContactRequestToCrm::class);
    }

    public function test_a_created_lead_is_delivered_with_its_prospect_id_and_the_same_payload_as_the_mapper(): void
    {
        $this->fakeStartEntreprise(self::leadResponse(201));
        $contactRequest = ContactRequest::factory()->create(['utm_source' => 'google', 'utm_campaign' => 'domiciliation']);

        $this->assertSame(CrmSyncOutcome::Delivered, $this->sync->handle($contactRequest));

        $this->assertSame(DeliveryStatus::Delivered, $contactRequest->crm_status);
        $this->assertSame(self::PROSPECT_ID, $contactRequest->crm_prospect_id);
        $this->assertTrue($contactRequest->crm_delivered_at->equalTo(now()));
        $this->assertSame(1, $contactRequest->crm_attempts);
        $this->assertNull($contactRequest->crm_claim_token);
        Http::assertSent(fn (Request $request): bool => $request->url() === self::LEADS_URL
            && $request->hasHeader('Idempotency-Key', $contactRequest->public_id)
            && $request->data() === LeadPayload::from($contactRequest)
            && $request->data()['attribution']['utm']['campaign'] === 'domiciliation');
    }

    public function test_a_replay_answer_also_counts_as_delivered(): void
    {
        $this->fakeStartEntreprise(self::leadResponse(200));
        $contactRequest = ContactRequest::factory()->create();

        $this->assertSame(CrmSyncOutcome::Delivered, $this->sync->handle($contactRequest));
        $this->assertSame(DeliveryStatus::Delivered, $contactRequest->crm_status);
    }

    /**
     * @param  list<string>  $fields
     */
    #[DataProvider('rejections')]
    public function test_requests_refused_by_startentreprise_are_kept_as_failed_for_investigation(int $status, string $code, array $fields, string $expected): void
    {
        $this->fakeStartEntreprise(self::problemResponse($status, $code, fields: $fields));
        $contactRequest = ContactRequest::factory()->create();

        $this->assertSame(CrmSyncOutcome::Rejected, $this->sync->handle($contactRequest));

        $this->assertSame(DeliveryStatus::Failed, $contactRequest->crm_status);
        $this->assertSame($expected, $contactRequest->crm_last_error);
        $this->assertNull($contactRequest->crm_next_attempt_at);
        $this->assertModelExists($contactRequest);
    }

    /**
     * @return array<string, array{int, string, list<string>, string}>
     */
    public static function rejections(): array
    {
        return [
            'validation' => [400, 'REQUEST_VALIDATION_FAILED', ['contact.email', 'inquiry.subject'],
                'http_400:REQUEST_VALIDATION_FAILED:contact.email,inquiry.subject'],
            'idempotency conflict' => [409, 'COMMERCIAL_LEAD_IDEMPOTENCY_CONFLICT', [], 'http_409:COMMERCIAL_LEAD_IDEMPOTENCY_CONFLICT'],
            'payload too large' => [413, 'REQUEST_TOO_LARGE', [], 'http_413:REQUEST_TOO_LARGE'],
        ];
    }

    #[DataProvider('configurationErrors')]
    public function test_configuration_errors_pause_synchronization_without_spending_the_requests_attempts(int $status, string $code): void
    {
        $this->fakeStartEntreprise(self::problemResponse($status, $code));
        $contactRequest = ContactRequest::factory()->create();

        $this->assertSame(CrmSyncOutcome::Paused, $this->sync->handle($contactRequest));

        $this->assertSame(DeliveryStatus::Pending, $contactRequest->crm_status);
        $this->assertSame(0, $contactRequest->crm_attempts);
        $this->assertSame($code, $contactRequest->crm_last_error);
        $this->assertTrue($contactRequest->crm_next_attempt_at->equalTo(now()->addSeconds(900)));
        $this->assertSame(['until' => now()->addSeconds(900)->toIso8601String(), 'reason' => $code], $this->sync->pause());
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function configurationErrors(): array
    {
        return [
            'missing scope' => [403, 'SCOPE_REQUIRED'],
            'missing entitlement' => [403, 'ENTITLEMENT_REQUIRED'],
            'disabled integration' => [403, 'ACCESS_DENIED'],
        ];
    }

    public function test_credentials_refused_after_a_token_renewal_pause_synchronization(): void
    {
        $this->fakeStartEntreprise(Http::response(['code' => 'UNAUTHORIZED'], 401));
        $contactRequest = ContactRequest::factory()->create();

        $this->assertSame(CrmSyncOutcome::Paused, $this->sync->handle($contactRequest));

        $this->assertSame('UNAUTHORIZED', $contactRequest->crm_last_error);
        $this->assertSame(0, $contactRequest->crm_attempts);
        Http::assertSentCount(4);
    }

    public function test_invalid_client_credentials_pause_synchronization(): void
    {
        $this->fakeStartEntreprise(self::leadResponse(), Http::response(['error' => 'invalid_client'], 401));
        $contactRequest = ContactRequest::factory()->create();

        $this->assertSame(CrmSyncOutcome::Paused, $this->sync->handle($contactRequest));
        $this->assertSame('token_invalid_client', $contactRequest->crm_last_error);
        $this->assertSame('token_invalid_client', $this->sync->pause()['reason']);
    }

    public function test_rate_limiting_waits_for_retry_after_without_counting_an_attempt(): void
    {
        $this->fakeStartEntreprise(self::problemResponse(429, 'RATE_LIMITED', ['Retry-After' => '120']));
        $contactRequest = ContactRequest::factory()->create();

        $this->assertSame(CrmSyncOutcome::Paused, $this->sync->handle($contactRequest));

        $this->assertSame(0, $contactRequest->crm_attempts);
        $this->assertTrue($contactRequest->crm_next_attempt_at->equalTo(now()->addSeconds(120)));
        $this->assertSame(now()->addSeconds(120)->toIso8601String(), $this->sync->pause()['until']);
    }

    public function test_a_missing_or_unavailable_assignee_pauses_for_the_announced_delay(): void
    {
        $this->fakeStartEntreprise(self::problemResponse(503, 'COMMERCIAL_LEAD_ASSIGNEE_NOT_CONFIGURED', ['Retry-After' => '300']));
        $contactRequest = ContactRequest::factory()->create();

        $this->assertSame(CrmSyncOutcome::Paused, $this->sync->handle($contactRequest));

        $this->assertSame(DeliveryStatus::Pending, $contactRequest->crm_status);
        $this->assertSame(0, $contactRequest->crm_attempts);
        $this->assertSame('COMMERCIAL_LEAD_ASSIGNEE_NOT_CONFIGURED', $this->sync->pause()['reason']);
        $this->assertTrue($contactRequest->crm_next_attempt_at->equalTo(now()->addSeconds(300)));
    }

    public function test_server_errors_retry_with_backoff_and_honour_a_longer_retry_after(): void
    {
        $this->fakeStartEntreprise(Http::sequence()
            ->pushResponse(self::problemResponse(500, 'PUBLIC_API_INTERNAL_ERROR'))
            ->pushResponse(self::problemResponse(503, 'SERVICE_UNAVAILABLE', ['Retry-After' => '600'])));
        $contactRequest = ContactRequest::factory()->create();

        $this->assertSame(CrmSyncOutcome::RetryScheduled, $this->sync->handle($contactRequest));
        $this->assertSame(1, $contactRequest->crm_attempts);
        $this->assertSame('PUBLIC_API_INTERNAL_ERROR', $contactRequest->crm_last_error);
        $this->assertTrue($contactRequest->crm_next_attempt_at->equalTo(now()->addSeconds(60)));

        $this->travel(60)->seconds();
        $this->assertSame(CrmSyncOutcome::RetryScheduled, $this->sync->handle($contactRequest));
        $this->assertSame(2, $contactRequest->crm_attempts);
        $this->assertTrue($contactRequest->crm_next_attempt_at->equalTo(now()->addSeconds(600)));
        $this->assertNull($this->sync->pause());
    }

    public function test_timeouts_and_network_failures_retry_with_the_same_external_id(): void
    {
        $this->fakeStartEntreprise(Http::sequence()
            ->pushFailedConnection('cURL error 28: Operation timed out')
            ->pushResponse(self::leadResponse(200)));
        $contactRequest = ContactRequest::factory()->create();

        $this->assertSame(CrmSyncOutcome::RetryScheduled, $this->sync->handle($contactRequest));
        $this->assertSame('connection_failed', $contactRequest->crm_last_error);

        $this->assertSame(CrmSyncOutcome::Skipped, $this->sync->handle($contactRequest), 'Retried before its backoff.');
        $this->travel(60)->seconds();
        $this->assertSame(CrmSyncOutcome::Delivered, $this->sync->handle($contactRequest));

        $externalIds = collect(Http::recorded())->map(fn (array $pair) => $pair[0])
            ->filter(fn (Request $request): bool => $request->url() === self::LEADS_URL)
            ->map(fn (Request $request): string => $request->header('Idempotency-Key')[0])->unique()->values()->all();
        $this->assertSame([$contactRequest->public_id], $externalIds);
    }

    public function test_temporary_failures_stop_after_the_attempt_limit_but_the_lead_is_kept(): void
    {
        $this->fakeStartEntreprise(self::problemResponse(502, 'BAD_GATEWAY'));
        $contactRequest = ContactRequest::factory()->create(['crm_attempts' => SyncContactRequestToCrm::MAX_ATTEMPTS - 1]);

        $this->assertSame(CrmSyncOutcome::Rejected, $this->sync->handle($contactRequest));

        $this->assertSame(DeliveryStatus::Failed, $contactRequest->crm_status);
        $this->assertSame('retries_exhausted:BAD_GATEWAY', $contactRequest->crm_last_error);
        $this->assertModelExists($contactRequest);
    }

    public function test_a_request_being_sent_cannot_be_sent_by_another_process(): void
    {
        $contactRequest = ContactRequest::factory()->create();
        $concurrentOutcome = null;
        $this->fakeStartEntreprise(function () use ($contactRequest, &$concurrentOutcome) {
            // A second scheduler run picks up the same row while the first is still waiting.
            $concurrentOutcome = $this->app->make(SyncContactRequestToCrm::class)
                ->handle(ContactRequest::findOrFail($contactRequest->id));

            return self::leadResponse();
        });

        $this->assertSame(CrmSyncOutcome::Delivered, $this->sync->handle($contactRequest));

        $this->assertSame(CrmSyncOutcome::Skipped, $concurrentOutcome);
        Http::assertSentCount(2);
    }

    public function test_an_expired_claim_is_recovered_and_a_live_one_is_left_alone(): void
    {
        $this->fakeStartEntreprise(self::leadResponse());
        $expired = ContactRequest::factory()->create([
            'crm_attempts' => 1, 'crm_claim_token' => 'a7f1c0de-0000-4000-8000-000000000000', 'crm_claimed_until' => now()->subSecond(),
        ]);
        $live = ContactRequest::factory()->create([
            'crm_attempts' => 1, 'crm_claim_token' => 'b7f1c0de-0000-4000-8000-000000000000', 'crm_claimed_until' => now()->addMinute(),
        ]);

        $this->assertSame(CrmSyncOutcome::Delivered, $this->sync->handle($expired));
        $this->assertSame(2, $expired->crm_attempts);
        $this->assertSame(CrmSyncOutcome::Skipped, $this->sync->handle($live));
    }

    public function test_delivered_failed_and_historical_requests_are_not_sent_automatically(): void
    {
        $this->fakeStartEntreprise(self::leadResponse());
        $delivered = ContactRequest::factory()->create(['crm_status' => DeliveryStatus::Delivered]);
        $failed = ContactRequest::factory()->create(['crm_status' => DeliveryStatus::Failed]);
        $historical = ContactRequest::factory()->create(['created_at' => now()->subHours(2)]);

        foreach ([$delivered, $failed, $historical] as $contactRequest) {
            $this->assertSame(CrmSyncOutcome::Skipped, $this->sync->handle($contactRequest));
        }
        Http::assertNothingSent();

        $historical->forceFill(['crm_backfill_requested_at' => now()])->save();
        $this->assertSame(CrmSyncOutcome::Delivered, $this->sync->handle($historical));
    }

    public function test_crm_and_email_deliveries_are_independent(): void
    {
        $this->fakeStartEntreprise(Http::sequence()
            ->pushResponse(self::leadResponse())
            ->pushResponse(self::problemResponse(400, 'REQUEST_VALIDATION_FAILED')));
        $emailFailed = ContactRequest::factory()->create(['notification_status' => DeliveryStatus::Failed]);
        $emailSent = ContactRequest::factory()->create(['notification_status' => DeliveryStatus::Delivered]);

        $this->assertSame(CrmSyncOutcome::Delivered, $this->sync->handle($emailFailed));
        $this->assertSame(CrmSyncOutcome::Rejected, $this->sync->handle($emailSent));

        $this->assertSame(DeliveryStatus::Failed, $emailFailed->notification_status);
        $this->assertSame(DeliveryStatus::Delivered, $emailSent->notification_status);
    }

    public function test_logs_carry_identifiers_and_codes_but_never_secrets_tokens_or_personal_data(): void
    {
        Log::spy();
        $this->fakeStartEntreprise(Http::sequence()
            ->pushResponse(self::leadResponse())
            ->pushResponse(self::problemResponse(400, 'REQUEST_VALIDATION_FAILED', fields: ['contact.email']))
            ->pushResponse(self::problemResponse(500, 'PUBLIC_API_INTERNAL_ERROR'))
            ->pushResponse(self::problemResponse(403, 'SCOPE_REQUIRED')));

        foreach (range(1, 4) as $index) {
            $this->sync->handle(ContactRequest::factory()->create([
                'name' => "Visiteur Secret {$index}", 'email' => "visiteur{$index}@example.com", 'phone' => '+212 6 99 99 99 99',
            ]));
        }

        $forbidden = ['test-client-secret', 'access-token-1', 'Visiteur Secret', '@example.com', '99 99 99'];
        foreach (['info', 'warning', 'error'] as $level) {
            Log::shouldHaveReceived($level)->withArgs(function (string $message, array $context = []) use ($forbidden): bool {
                foreach ($forbidden as $value) {
                    $this->assertStringNotContainsString($value, $message.json_encode($context));
                }

                return true;
            });
        }
        Log::shouldHaveReceived('error')->with('Contact request rejected by StartEntreprise; investigation required.', Mockery::on(
            fn (array $context): bool => $context['error_code'] === 'http_400:REQUEST_VALIDATION_FAILED:contact.email',
        ));
    }
}
