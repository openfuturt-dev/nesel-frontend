<?php

namespace App\Console\Commands;

use App\Actions\SyncContactRequestToCrm;
use App\Enums\DeliveryStatus;
use App\Models\ContactRequest;
use App\Support\StartEntreprise\StartEntrepriseClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('contact-requests:crm-monitor {--delayed-after=30 : Minutes after which an undelivered request counts as delayed}')]
#[Description('Report StartEntreprise CRM synchronization health; exits non-zero when it needs attention')]
class MonitorCrmSynchronization extends Command
{
    private const LISTED_IDS = 20;

    /**
     * Execute the console command.
     */
    public function handle(StartEntrepriseClient $client, SyncContactRequestToCrm $sync): int
    {
        if (! $client->enabled()) {
            $this->components->info('StartEntreprise synchronization is disabled.');

            return self::SUCCESS;
        }

        $delayedAfter = max(1, (int) $this->option('delayed-after'));
        $problems = $client->configurationProblems();
        $pause = $sync->pause();
        $scheduledPending = $sync->scheduled()->where('crm_status', DeliveryStatus::Pending);
        $delayed = (clone $scheduledPending)
            ->whereRaw('COALESCE(crm_backfill_requested_at, created_at) <= ?', [now()->subMinutes($delayedAfter)])
            ->oldest('id')->pluck('public_id');
        $failed = ContactRequest::query()->where('crm_status', DeliveryStatus::Failed)
            ->latest('id')->get(['public_id', 'crm_last_error']);
        $notScheduled = ContactRequest::query()->where('crm_status', DeliveryStatus::Pending)
            ->whereNotIn('id', $sync->scheduled()->select('id'))->count();
        $lastDelivery = ContactRequest::query()->max('crm_delivered_at');

        $this->table(['CRM synchronization', 'Value'], [
            ['Synchronizing requests created since', $client->syncFrom()?->toIso8601String() ?? 'not configured'],
            ['Pending', (clone $scheduledPending)->count()],
            ["Delayed (pending > {$delayedAfter} min)", $delayed->count()],
            ['Failed (needs investigation)', $failed->count()],
            ['Delivered', ContactRequest::query()->where('crm_status', DeliveryStatus::Delivered)->count()],
            ['Older requests not scheduled (see crm-backfill)', $notScheduled],
            ['Last successful delivery', $lastDelivery ?? 'never'],
            ['Paused', $pause === null ? 'no' : "until {$pause['until']} ({$pause['reason']})"],
        ]);

        foreach ($problems as $problem) {
            $this->components->error("Configuration: {$problem}.");
        }
        if ($delayed->isNotEmpty()) {
            $this->components->warn('Delayed: '.$delayed->take(self::LISTED_IDS)->implode(', '));
        }
        foreach ($failed->take(self::LISTED_IDS) as $request) {
            $this->components->error("Failed: {$request->public_id} ({$request->crm_last_error})");
        }

        $configurationPause = $pause !== null && $pause['reason'] !== 'RATE_LIMITED';
        $healthy = $problems === [] && ! $configurationPause && $delayed->isEmpty() && $failed->isEmpty();

        if (! $healthy) {
            Log::warning('StartEntreprise CRM synchronization needs attention.', [
                'configuration_problems' => $problems,
                'paused_reason' => $pause['reason'] ?? null,
                'delayed' => $delayed->count(),
                'failed' => $failed->count(),
                'contact_request_ids' => $delayed->merge($failed->pluck('public_id'))->take(self::LISTED_IDS)->values()->all(),
            ]);
        }

        return $healthy ? self::SUCCESS : self::FAILURE;
    }
}
