<?php

namespace App\Console\Commands;

use App\Actions\SyncContactRequestToCrm;
use App\Enums\DeliveryStatus;
use App\Models\ContactRequest;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

#[Signature('contact-requests:crm-requeue
    {--id=* : Failed requests to send again, by public ID}
    {--all-failed : Every failed request}
    {--error= : Only failed requests whose error code starts with this}')]
#[Description('After a repair: lift a synchronization pause and send failed contact requests again')]
class RequeueContactRequestsToCrm extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(SyncContactRequestToCrm $sync): int
    {
        if ($pause = $sync->pause()) {
            $sync->resume();
            $this->components->info("Pause lifted ({$pause['reason']}).");
        }

        $ids = array_filter((array) $this->option('id'));

        if ($ids === [] && ! $this->option('all-failed')) {
            return self::SUCCESS;
        }

        // Same public ID as before, so StartEntreprise replays instead of duplicating.
        $requeued = ContactRequest::query()
            ->where('crm_status', DeliveryStatus::Failed)
            ->when($ids !== [], fn (Builder $query) => $query->whereIn('public_id', $ids))
            // "!" is an explicit LIKE escape: SQLite and PostgreSQL have no default one.
            ->when(filled($this->option('error')), fn (Builder $query) => $query->whereRaw("crm_last_error LIKE ? ESCAPE '!'", [
                str_replace(['!', '%', '_'], ['!!', '!%', '!_'], (string) $this->option('error')).'%',
            ]))
            ->update([
                'crm_status' => DeliveryStatus::Pending,
                'crm_attempts' => 0,
                'crm_next_attempt_at' => null,
                'crm_claim_token' => null,
                'crm_claimed_until' => null,
            ]);

        $this->components->info("{$requeued} failed request(s) queued again.");

        return self::SUCCESS;
    }
}
