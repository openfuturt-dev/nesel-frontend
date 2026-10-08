<?php

namespace App\Console\Commands;

use App\Enums\DeliveryStatus;
use App\Models\ContactRequest;
use App\Support\StartEntreprise\StartEntrepriseClient;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

#[Signature('contact-requests:crm-backfill
    {--from= : Only requests created at or after this date (ISO-8601)}
    {--until= : Only requests created before this date (ISO-8601)}
    {--id=* : Only these public IDs}
    {--dry-run : List what would be scheduled without changing anything}
    {--force : Do not ask for confirmation}')]
#[Description('Schedule contact requests created before the synchronization start for delivery to StartEntreprise')]
class BackfillContactRequestsToCrm extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(StartEntrepriseClient $client): int
    {
        $ids = array_filter((array) $this->option('id'));

        if ($ids === [] && blank($this->option('from')) && blank($this->option('until'))) {
            $this->components->error('Choose the requests to backfill with --from, --until or --id.');

            return self::INVALID;
        }

        try {
            $from = $this->date('from');
            $until = $this->date('until');
        } catch (Throwable) {
            $this->components->error('--from and --until must be valid dates.');

            return self::INVALID;
        }

        $syncFrom = $client->syncFrom();
        // Only requests the automatic synchronization will never pick up, never sent before.
        $query = ContactRequest::query()
            ->where('crm_status', DeliveryStatus::Pending)
            ->where('crm_attempts', 0)
            ->whereNull('crm_backfill_requested_at')
            ->when($syncFrom, fn (Builder $query) => $query->where('created_at', '<', $syncFrom))
            ->when($from, fn (Builder $query) => $query->where('created_at', '>=', $from))
            ->when($until, fn (Builder $query) => $query->where('created_at', '<', $until))
            ->when($ids !== [], fn (Builder $query) => $query->whereIn('public_id', $ids));

        $candidates = (clone $query)->orderBy('id')->pluck('public_id');

        if ($candidates->isEmpty()) {
            $this->components->info('No historical contact request matches.');

            return self::SUCCESS;
        }

        $this->components->info("{$candidates->count()} historical contact request(s) match: ".$candidates->take(20)->implode(', ')
            .($candidates->count() > 20 ? ', …' : ''));

        if ($this->option('dry-run')) {
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Schedule them for delivery to StartEntreprise with their original IDs?')) {
            return self::FAILURE;
        }

        $scheduled = $query->whereIn('public_id', $candidates)->update(['crm_backfill_requested_at' => now()]);
        $this->components->info("{$scheduled} request(s) scheduled; contact-requests:crm-sync will send them.");

        return self::SUCCESS;
    }

    private function date(string $option): ?CarbonImmutable
    {
        $value = $this->option($option);

        return blank($value) ? null : CarbonImmutable::parse((string) $value)->utc();
    }
}
