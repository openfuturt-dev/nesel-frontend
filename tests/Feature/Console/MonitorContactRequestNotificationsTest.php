<?php

namespace Tests\Feature\Console;

use App\Enums\DeliveryStatus;
use App\Models\ContactRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class MonitorContactRequestNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_succeeds_when_nothing_is_delayed_or_failed(): void
    {
        ContactRequest::factory()->create(['notification_status' => DeliveryStatus::Delivered]);
        ContactRequest::factory()->create();

        $this->artisan('contact-requests:monitor')
            ->expectsTable(['Notification status', 'Contact requests'], [
                ['Pending', 1],
                ['Delivered', 1],
                ['Failed', 0],
                ['Delayed (pending > 30 min)', 0],
            ])
            ->assertSuccessful();
    }

    public function test_it_reports_delayed_and_failed_requests_by_public_id_only(): void
    {
        Log::spy();
        $delayed = ContactRequest::factory()->create(['email' => 'nassim@example.com', 'created_at' => now()->subMinutes(31)]);
        ContactRequest::factory()->create(['created_at' => now()->subMinutes(29)]);
        $failed = ContactRequest::factory()->create(['notification_status' => DeliveryStatus::Failed]);

        $this->artisan('contact-requests:monitor')
            ->expectsOutputToContain($delayed->public_id)
            ->expectsOutputToContain($failed->public_id)
            ->doesntExpectOutputToContain('nassim@example.com')
            ->assertFailed();

        Log::shouldHaveReceived('warning')->once()->with('Contact request notifications delayed.', Mockery::on(
            fn (array $context): bool => $context['delayed'] === 1 && $context['contact_request_ids'] === [$delayed->public_id],
        ));
    }

    public function test_the_delay_threshold_is_configurable(): void
    {
        ContactRequest::factory()->create(['created_at' => now()->subMinutes(10)]);

        $this->artisan('contact-requests:monitor', ['--delayed-after' => 5])->assertFailed();
        $this->artisan('contact-requests:monitor', ['--delayed-after' => 15])->assertSuccessful();
    }
}
