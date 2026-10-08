<?php

namespace App\Console\Commands;

use App\Enums\DeliveryStatus;
use App\Models\ContactRequest;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('contact-requests:monitor {--delayed-after=30 : Minutes after submission when an undelivered email counts as delayed}')]
#[Description('Report contact request email delivery health; exits non-zero when emails are delayed or failed')]
class MonitorContactRequestNotifications extends Command
{
    /** Identifiers listed per problem category, to keep output and logs short. */
    private const LISTED_IDS = 20;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $delayedAfter = max(1, (int) $this->option('delayed-after'));

        $counts = ContactRequest::query()->toBase()
            ->selectRaw('notification_status, count(*) as total')
            ->groupBy('notification_status')
            ->pluck('total', 'notification_status');

        $delayed = ContactRequest::query()
            ->where('notification_status', DeliveryStatus::Pending)
            ->where('created_at', '<=', now()->subMinutes($delayedAfter))
            ->oldest('id')
            ->pluck('public_id');

        $failed = ContactRequest::query()
            ->where('notification_status', DeliveryStatus::Failed)
            ->latest('id')
            ->pluck('public_id');

        $this->table(['Notification status', 'Contact requests'], [
            ['Pending', (int) $counts->get(DeliveryStatus::Pending->value, 0)],
            ['Delivered', (int) $counts->get(DeliveryStatus::Delivered->value, 0)],
            ['Failed', $failed->count()],
            ["Delayed (pending > {$delayedAfter} min)", $delayed->count()],
        ]);

        if ($delayed->isNotEmpty()) {
            $this->components->warn('Delayed: '.$delayed->take(self::LISTED_IDS)->implode(', '));

            Log::warning('Contact request notifications delayed.', [
                'delayed' => $delayed->count(),
                'delayed_after_minutes' => $delayedAfter,
                'contact_request_ids' => $delayed->take(self::LISTED_IDS)->all(),
            ]);
        }

        if ($failed->isNotEmpty()) {
            $this->components->error('Failed: '.$failed->take(self::LISTED_IDS)->implode(', '));
        }

        return $delayed->isEmpty() && $failed->isEmpty() ? self::SUCCESS : self::FAILURE;
    }
}
