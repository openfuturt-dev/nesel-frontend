<?php

namespace Tests\Feature\Console;

use App\Actions\SyncContactRequestToCrm;
use App\Enums\DeliveryStatus;
use App\Models\ContactRequest;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\FakesStartEntreprise;
use Tests\TestCase;

class CrmSynchronizationCommandsTest extends TestCase
{
    use FakesStartEntreprise, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeSecond();
    }

    public function test_nothing_is_sent_while_synchronization_is_disabled(): void
    {
        Http::preventStrayRequests();
        ContactRequest::factory()->create();

        $this->artisan('contact-requests:crm-sync')
            ->expectsOutputToContain('synchronization is disabled')
            ->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(0, ContactRequest::sole()->crm_attempts);
    }

    public function test_an_enabled_but_incomplete_configuration_sends_nothing_and_fails_visibly(): void
    {
        Http::preventStrayRequests();
        $this->enableStartEntreprise();
        config()->set('services.startentreprise.client_secret', null);
        config()->set('services.startentreprise.leads_sync_from', null);
        ContactRequest::factory()->create();

        $this->artisan('contact-requests:crm-sync')
            ->expectsOutputToContain('client_secret is missing; leads_sync_from is missing or not a valid date')
            ->assertFailed();

        Http::assertNothingSent();
    }

    public function test_due_requests_are_sent_oldest_first_in_bounded_batches(): void
    {
        $this->enableStartEntreprise();
        $this->fakeStartEntreprise(self::leadResponse());
        $requests = ContactRequest::factory()->count(3)->create();

        $this->artisan('contact-requests:crm-sync', ['--limit' => 2])
            ->expectsOutputToContain('Delivered: 2. Rejected: 0. Retry scheduled: 0. Paused: 0. Skipped: 0.')
            ->assertSuccessful();

        $this->assertSame(
            [DeliveryStatus::Delivered, DeliveryStatus::Delivered, DeliveryStatus::Pending],
            $requests->map(fn (ContactRequest $request) => $request->fresh()->crm_status)->all(),
        );
    }

    public function test_an_outage_stops_the_batch_instead_of_hammering_startentreprise(): void
    {
        $this->enableStartEntreprise();
        $this->fakeStartEntreprise(self::problemResponse(502, 'BAD_GATEWAY'));
        [$first, $second] = ContactRequest::factory()->count(2)->create()->all();

        $this->artisan('contact-requests:crm-sync')->assertSuccessful();

        $this->assertSame(1, $first->fresh()->crm_attempts);
        $this->assertSame(0, $second->fresh()->crm_attempts);
    }

    public function test_a_configuration_pause_stops_later_runs_until_requeue_lifts_it(): void
    {
        $this->enableStartEntreprise();
        $this->fakeStartEntreprise(Http::sequence()
            ->pushResponse(self::problemResponse(403, 'ENTITLEMENT_REQUIRED'))
            ->pushResponse(self::leadResponse())
            ->pushResponse(self::leadResponse()));
        ContactRequest::factory()->count(2)->create();

        $this->artisan('contact-requests:crm-sync')->assertSuccessful();
        $this->artisan('contact-requests:crm-sync')
            ->expectsOutputToContain('(ENTITLEMENT_REQUIRED)')
            ->assertSuccessful();
        Http::assertSentCount(2); // One token, one lead: the paused run sent nothing.

        $this->artisan('contact-requests:crm-requeue')
            ->expectsOutputToContain('Pause lifted (ENTITLEMENT_REQUIRED).')
            ->assertSuccessful();
        $this->travel(15)->minutes();
        $this->artisan('contact-requests:crm-sync')->assertSuccessful();

        $this->assertSame(2, ContactRequest::query()->where('crm_status', DeliveryStatus::Delivered)->count());
    }

    public function test_failed_requests_are_only_sent_again_when_requeued_and_keep_their_external_id(): void
    {
        $this->enableStartEntreprise();
        $this->fakeStartEntreprise(self::leadResponse(200));
        $conflict = ContactRequest::factory()->create([
            'crm_status' => DeliveryStatus::Failed, 'crm_attempts' => 1, 'crm_last_error' => 'http_409:COMMERCIAL_LEAD_IDEMPOTENCY_CONFLICT',
        ]);
        $invalid = ContactRequest::factory()->create([
            'crm_status' => DeliveryStatus::Failed, 'crm_attempts' => 1, 'crm_last_error' => 'http_400:REQUEST_VALIDATION_FAILED',
        ]);

        $this->artisan('contact-requests:crm-sync')->assertSuccessful();
        Http::assertNothingSent();

        $this->artisan('contact-requests:crm-requeue', ['--all-failed' => true, '--error' => 'http_400'])
            ->expectsOutputToContain('1 failed request(s) queued again.')
            ->assertSuccessful();
        $this->artisan('contact-requests:crm-sync')->assertSuccessful();

        $this->assertSame(DeliveryStatus::Delivered, $invalid->fresh()->crm_status);
        $this->assertSame(DeliveryStatus::Failed, $conflict->fresh()->crm_status);
        Http::assertSent(fn (Request $request): bool => $request->url() === self::LEADS_URL
            && $request['externalId'] === $invalid->public_id);
    }

    public function test_historical_requests_wait_for_an_explicit_backfill(): void
    {
        $this->enableStartEntreprise(now()->subDay()->toIso8601String());
        $this->fakeStartEntreprise(self::leadResponse());
        $old = ContactRequest::factory()->create(['created_at' => now()->subDays(10)]);
        $older = ContactRequest::factory()->create(['created_at' => now()->subDays(40)]);
        $new = ContactRequest::factory()->create();

        $this->artisan('contact-requests:crm-sync')->assertSuccessful();
        $this->assertSame(DeliveryStatus::Delivered, $new->fresh()->crm_status);
        $this->assertSame(DeliveryStatus::Pending, $old->fresh()->crm_status);

        $this->artisan('contact-requests:crm-backfill')->assertExitCode(2);
        $this->artisan('contact-requests:crm-backfill', ['--from' => now()->subDays(30)->toDateString(), '--dry-run' => true])
            ->expectsOutputToContain('1 historical contact request(s) match: '.$old->public_id)
            ->assertSuccessful();
        $this->assertNull($old->fresh()->crm_backfill_requested_at);

        $this->artisan('contact-requests:crm-backfill', ['--from' => now()->subDays(30)->toDateString()])
            ->expectsConfirmation('Schedule them for delivery to StartEntreprise with their original IDs?', 'yes')
            ->expectsOutputToContain('1 request(s) scheduled')
            ->assertSuccessful();
        $this->artisan('contact-requests:crm-sync')->assertSuccessful();

        $this->assertSame(DeliveryStatus::Delivered, $old->fresh()->crm_status);
        $this->assertSame(DeliveryStatus::Pending, $older->fresh()->crm_status);
        Http::assertSent(fn (Request $request): bool => $request->url() === self::LEADS_URL
            && $request['externalId'] === $old->public_id && $request->hasHeader('Idempotency-Key', $old->public_id));
    }

    public function test_backfill_by_id_skips_requests_that_are_already_synchronized_automatically(): void
    {
        $this->enableStartEntreprise(now()->subDay()->toIso8601String());
        $old = ContactRequest::factory()->create(['created_at' => now()->subDays(3)]);
        $recent = ContactRequest::factory()->create();

        $this->artisan('contact-requests:crm-backfill', ['--id' => [$old->public_id, $recent->public_id], '--force' => true])
            ->expectsOutputToContain('1 request(s) scheduled')
            ->assertSuccessful();

        $this->assertNotNull($old->fresh()->crm_backfill_requested_at);
        $this->assertNull($recent->fresh()->crm_backfill_requested_at);
    }

    public function test_requests_abandoned_on_their_last_attempt_are_failed_not_lost(): void
    {
        $this->enableStartEntreprise();
        $this->fakeStartEntreprise(self::leadResponse());
        $abandoned = ContactRequest::factory()->create([
            'crm_attempts' => SyncContactRequestToCrm::MAX_ATTEMPTS,
            'crm_claim_token' => 'a7f1c0de-0000-4000-8000-000000000000', 'crm_claimed_until' => now()->subSecond(),
        ]);

        $this->artisan('contact-requests:crm-sync')->expectsOutputToContain('Rejected: 1.')->assertSuccessful();

        $this->assertSame(DeliveryStatus::Failed, $abandoned->fresh()->crm_status);
        $this->assertSame('claim_expired', $abandoned->fresh()->crm_last_error);
    }

    public function test_the_monitor_reports_backlog_failures_pauses_and_the_last_delivery(): void
    {
        $this->artisan('contact-requests:crm-monitor')->expectsOutputToContain('disabled')->assertSuccessful();

        $this->enableStartEntreprise();
        ContactRequest::factory()->create(['crm_status' => DeliveryStatus::Delivered, 'crm_delivered_at' => now()->subMinutes(5)]);
        ContactRequest::factory()->create(['created_at' => now()->subHours(3)]);
        $this->artisan('contact-requests:crm-monitor')
            ->expectsTable(['CRM synchronization', 'Value'], [
                ['Synchronizing requests created since', now()->subHour()->toIso8601String()],
                ['Pending', 0],
                ['Delayed (pending > 30 min)', 0],
                ['Failed (needs investigation)', 0],
                ['Delivered', 1],
                ['Older requests not scheduled (see crm-backfill)', 1],
                ['Last successful delivery', now()->subMinutes(5)->format('Y-m-d H:i:s')],
                ['Paused', 'no'],
            ])
            ->assertSuccessful();

        $delayed = ContactRequest::factory()->create(['created_at' => now()->subMinutes(45)]);
        $failed = ContactRequest::factory()->create(['crm_status' => DeliveryStatus::Failed, 'crm_last_error' => 'http_409:COMMERCIAL_LEAD_IDEMPOTENCY_CONFLICT']);
        Cache::put(SyncContactRequestToCrm::PAUSE_CACHE_KEY, ['until' => now()->addMinutes(10)->toIso8601String(), 'reason' => 'SCOPE_REQUIRED'], 600);

        $this->artisan('contact-requests:crm-monitor')
            ->expectsOutputToContain("Delayed: {$delayed->public_id}")
            ->expectsOutputToContain("Failed: {$failed->public_id} (http_409:COMMERCIAL_LEAD_IDEMPOTENCY_CONFLICT)")
            ->expectsOutputToContain('(SCOPE_REQUIRED)')
            ->assertFailed();
    }

    public function test_crm_synchronization_is_scheduled_every_minute_beside_the_email_schedule(): void
    {
        Artisan::all();
        $events = collect($this->app->make(Schedule::class)->events());
        $event = fn (string $command): Event => $events->sole(
            fn (Event $event): bool => str_ends_with((string) $event->command, $command));

        $this->assertSame('* * * * *', $event('contact-requests:crm-sync')->expression);
        $this->assertTrue($event('contact-requests:crm-sync')->withoutOverlapping);
        $this->assertSame(10, $event('contact-requests:crm-sync')->expiresAt);
        $this->assertSame('0 * * * *', $event('contact-requests:crm-monitor')->expression);
        $this->assertSame('* * * * *', $event('contact-requests:dispatch')->expression);
        $this->assertSame('0 * * * *', $event('contact-requests:monitor')->expression);
    }
}
