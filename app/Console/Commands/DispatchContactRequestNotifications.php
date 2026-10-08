<?php

namespace App\Console\Commands;

use App\Actions\SendContactRequestNotification;
use App\Enums\DeliveryStatus;
use App\Models\ContactRequest;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('contact-requests:dispatch {--limit=50 : Maximum number of notifications to attempt in this run}')]
#[Description('Send due contact request emails: first attempts the request missed, and scheduled retries')]
class DispatchContactRequestNotifications extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SendContactRequestNotification $sendNotification): int
    {
        $abandoned = $sendNotification->failAbandoned();

        // Each row is claimed again inside handle(), so a concurrent run that
        // selected the same rows skips them instead of sending twice.
        $outcomes = $sendNotification->due()
            ->orderBy('id')
            ->limit(max(1, (int) $this->option('limit')))
            ->get()
            ->map(fn (ContactRequest $contactRequest): ?DeliveryStatus => $sendNotification->handle($contactRequest));

        $this->components->info(sprintf(
            'Delivered: %d. Retry scheduled: %d. Failed: %d. Skipped: %d.',
            $outcomes->filter(fn (?DeliveryStatus $status): bool => $status === DeliveryStatus::Delivered)->count(),
            $outcomes->filter(fn (?DeliveryStatus $status): bool => $status === DeliveryStatus::Pending)->count(),
            $outcomes->filter(fn (?DeliveryStatus $status): bool => $status === DeliveryStatus::Failed)->count() + $abandoned,
            $outcomes->filter(fn (?DeliveryStatus $status): bool => $status === null)->count(),
        ));

        return self::SUCCESS;
    }
}
