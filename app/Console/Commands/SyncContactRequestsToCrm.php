<?php

namespace App\Console\Commands;

use App\Actions\SyncContactRequestToCrm;
use App\Enums\CrmSyncOutcome;
use App\Models\ContactRequest;
use App\Support\StartEntreprise\StartEntrepriseClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('contact-requests:crm-sync {--limit=25 : Maximum number of requests to send in this run}')]
#[Description('Send due contact requests to the StartEntreprise commercial CRM')]
class SyncContactRequestsToCrm extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(StartEntrepriseClient $client, SyncContactRequestToCrm $sync): int
    {
        if (! $client->enabled()) {
            $this->components->info('StartEntreprise synchronization is disabled (STARTENTREPRISE_LEADS_ENABLED).');

            return self::SUCCESS;
        }

        if ($problems = $client->configurationProblems()) {
            Log::error('StartEntreprise CRM synchronization is enabled but not configured.', ['problems' => $problems]);
            $this->components->error('Configuration incomplete: '.implode('; ', $problems).'.');

            return self::FAILURE;
        }

        if ($pause = $sync->pause()) {
            $this->components->warn("Paused until {$pause['until']} ({$pause['reason']}).");

            return self::SUCCESS;
        }

        $abandoned = $sync->failAbandoned();
        $outcomes = collect();

        foreach ($sync->due()->orderBy('id')->limit(max(1, (int) $this->option('limit')))->get() as $contactRequest) {
            /** @var ContactRequest $contactRequest */
            $outcome = $sync->handle($contactRequest);
            $outcomes->push($outcome);

            if ($outcome->stopsBatch()) {
                break;
            }
        }

        $count = fn (CrmSyncOutcome $outcome): int => $outcomes->filter(fn (CrmSyncOutcome $value): bool => $value === $outcome)->count();
        $this->components->info(sprintf(
            'Delivered: %d. Rejected: %d. Retry scheduled: %d. Paused: %d. Skipped: %d.',
            $count(CrmSyncOutcome::Delivered),
            $count(CrmSyncOutcome::Rejected) + $abandoned,
            $count(CrmSyncOutcome::RetryScheduled),
            $count(CrmSyncOutcome::Paused),
            $count(CrmSyncOutcome::Skipped),
        ));

        return self::SUCCESS;
    }
}
