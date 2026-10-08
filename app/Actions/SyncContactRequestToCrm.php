<?php

namespace App\Actions;

use App\Enums\CrmSyncOutcome;
use App\Enums\DeliveryStatus;
use App\Models\ContactRequest;
use App\Support\StartEntreprise\LeadPayload;
use App\Support\StartEntreprise\StartEntrepriseClient;
use App\Support\StartEntreprise\StartEntrepriseTokenException;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Sends a stored contact request to the StartEntreprise commercial CRM, independently of its email
 * notification.
 *
 * A row is claimed with one conditional UPDATE, so two processes never send it at the same time,
 * and every attempt reuses the row's public ID as externalId and Idempotency-Key, so a retry after
 * a lost response is answered by StartEntreprise as a replay instead of a second prospect.
 *
 * Problems that concern every request (credentials, scope, entitlement, assignee, rate limit) do
 * not count against the row: they pause the whole synchronization instead, so a configuration
 * mistake neither fails nor burns the retries of every waiting lead. Stored leads are never deleted.
 */
class SyncContactRequestToCrm
{
    /**
     * Seconds before the next attempt after a temporary failure, keyed by attempts already made;
     * later attempts wait MAX_RETRY_DELAY.
     *
     * @var array<int, int>
     */
    public const RETRY_DELAYS = [1 => 60, 2 => 300, 3 => 900, 4 => 3600];

    public const MAX_RETRY_DELAY = 21600;

    /** About a week of temporary failures before a request needs a person to look at it. */
    public const MAX_ATTEMPTS = 30;

    public const CLAIM_SECONDS = 300;

    /** How long a configuration problem pauses synchronization before it is tried again. */
    public const CONFIGURATION_PAUSE_SECONDS = 900;

    public const PAUSE_CACHE_KEY = 'startentreprise:leads:paused';

    public function __construct(private readonly StartEntrepriseClient $client) {}

    /**
     * Requests that may be sent now: pending, inside the synchronization window (or explicitly
     * backfilled), past their backoff delay and not claimed by a live process.
     *
     * @return Builder<ContactRequest>
     */
    public function due(): Builder
    {
        $now = now();

        return $this->scheduled()
            ->where('crm_status', DeliveryStatus::Pending)
            ->where('crm_attempts', '<', self::MAX_ATTEMPTS)
            ->where(fn (Builder $query) => $query
                ->whereNull('crm_next_attempt_at')
                ->orWhere('crm_next_attempt_at', '<=', $now))
            ->where(fn (Builder $query) => $query
                ->whereNull('crm_claimed_until')
                ->orWhere('crm_claimed_until', '<=', $now));
    }

    /**
     * Requests the synchronization is responsible for: created since the activation instant, or
     * scheduled by an administrator's backfill. Older requests are never sent automatically.
     *
     * @return Builder<ContactRequest>
     */
    public function scheduled(): Builder
    {
        $syncFrom = $this->client->syncFrom();

        return ContactRequest::query()->where(fn (Builder $query) => $syncFrom === null
            ? $query->whereNotNull('crm_backfill_requested_at')
            : $query->where('created_at', '>=', $syncFrom)->orWhereNotNull('crm_backfill_requested_at'));
    }

    /**
     * @return array{until: string, reason: string}|null
     */
    public function pause(): ?array
    {
        $pause = Cache::get(self::PAUSE_CACHE_KEY);

        return is_array($pause) ? $pause : null;
    }

    public function resume(): void
    {
        Cache::forget(self::PAUSE_CACHE_KEY);
    }

    public function handle(ContactRequest $contactRequest): CrmSyncOutcome
    {
        $claimToken = (string) Str::uuid();

        $claimed = $this->due()->whereKey($contactRequest->getKey())->update([
            'crm_claim_token' => $claimToken,
            'crm_claimed_until' => now()->addSeconds(self::CLAIM_SECONDS),
            'crm_attempts' => DB::raw('crm_attempts + 1'),
            'crm_last_attempted_at' => now(),
        ]);

        if ($claimed === 0) {
            return CrmSyncOutcome::Skipped;
        }

        $contactRequest->refresh();

        try {
            $response = $this->client->submitLead(LeadPayload::from($contactRequest));
        } catch (StartEntrepriseTokenException $tokenFailure) {
            return $tokenFailure->retryable
                ? $this->retry($contactRequest, $claimToken, $tokenFailure->errorCode)
                : $this->pauseFor($contactRequest, $claimToken, $tokenFailure->errorCode, self::CONFIGURATION_PAUSE_SECONDS);
        } catch (ConnectionException) {
            // The lead may have reached StartEntreprise before the timeout; the replay answers that.
            return $this->retry($contactRequest, $claimToken, 'connection_failed');
        } catch (Throwable $unexpected) {
            Log::error('Unexpected error while sending a contact request to StartEntreprise.', [
                'contact_request_id' => $contactRequest->public_id,
                'exception' => class_basename($unexpected),
            ]);

            return $this->retry($contactRequest, $claimToken, 'unexpected_error');
        }

        return $this->record($contactRequest, $claimToken, $response);
    }

    /**
     * Fail requests whose last allowed attempt never reported back because the process died while
     * holding the claim.
     */
    public function failAbandoned(): int
    {
        $abandoned = ContactRequest::query()
            ->where('crm_status', DeliveryStatus::Pending)
            ->where('crm_attempts', '>=', self::MAX_ATTEMPTS)
            ->where(fn (Builder $query) => $query
                ->whereNull('crm_claimed_until')
                ->orWhere('crm_claimed_until', '<=', now()))
            ->pluck('public_id', 'id');

        if ($abandoned->isNotEmpty()) {
            ContactRequest::query()->whereKey($abandoned->keys())->update([
                'crm_status' => DeliveryStatus::Failed,
                'crm_last_error' => 'claim_expired',
                'crm_next_attempt_at' => null,
                'crm_claim_token' => null,
                'crm_claimed_until' => null,
            ]);

            foreach ($abandoned as $publicId) {
                Log::error('Contact request could not be delivered to StartEntreprise.', [
                    'contact_request_id' => $publicId,
                    'error_code' => 'claim_expired',
                ]);
            }
        }

        return $abandoned->count();
    }

    private function record(ContactRequest $contactRequest, string $claimToken, Response $response): CrmSyncOutcome
    {
        $status = $response->status();
        $code = $response->json('code');
        $code = is_string($code) && preg_match('/^[A-Z0-9_]{1,80}$/', $code) ? $code : null;
        $prospectId = $response->json('prospectId');
        $retryAfter = $this->retryAfter($response);

        return match (true) {
            ($status === 200 || $status === 201) && is_string($prospectId) && Str::isUuid($prospectId) => $this->delivered($contactRequest, $prospectId, replayed: $status === 200),
            $status >= 200 && $status < 300 => $this->retry($contactRequest, $claimToken, 'invalid_response'),
            // Still 401 after a fresh token: the credentials themselves are refused.
            $status === 401 => $this->pauseFor($contactRequest, $claimToken, 'UNAUTHORIZED', self::CONFIGURATION_PAUSE_SECONDS),
            // Scope, entitlement, disabled integration, or a wrong API URL (redirects are not followed).
            ($status >= 300 && $status < 400) || in_array($status, [403, 404, 405, 415], true) => $this->pauseFor($contactRequest, $claimToken, $code ?? "http_{$status}", self::CONFIGURATION_PAUSE_SECONDS),
            $status === 429 => $this->pauseFor($contactRequest, $claimToken, $code ?? 'RATE_LIMITED', $retryAfter ?? 60),
            // The organization has no usable assignee: nobody would follow the lead up.
            $status === 503 && $code !== null && str_starts_with($code, 'COMMERCIAL_LEAD_ASSIGNEE_') => $this->pauseFor($contactRequest, $claimToken, $code, $retryAfter ?? 300),
            $status >= 500 => $this->retry($contactRequest, $claimToken, $code ?? "http_{$status}", $retryAfter),
            default => $this->reject($contactRequest, $claimToken, $this->rejection($status, $code, $response)),
        };
    }

    private function delivered(ContactRequest $contactRequest, string $prospectId, bool $replayed): CrmSyncOutcome
    {
        // Not conditional on the claim: StartEntreprise has the lead, whatever else happened.
        ContactRequest::query()->whereKey($contactRequest->getKey())->update([
            'crm_status' => DeliveryStatus::Delivered,
            'crm_prospect_id' => $prospectId,
            'crm_delivered_at' => now(),
            'crm_last_error' => null,
            'crm_next_attempt_at' => null,
            'crm_claim_token' => null,
            'crm_claimed_until' => null,
        ]);

        Log::info('Contact request delivered to StartEntreprise.', [
            'contact_request_id' => $contactRequest->public_id,
            'prospect_id' => $prospectId,
            'attempt' => $contactRequest->crm_attempts,
            'replayed' => $replayed,
        ]);

        $contactRequest->refresh();

        return CrmSyncOutcome::Delivered;
    }

    private function retry(ContactRequest $contactRequest, string $claimToken, string $errorCode, ?int $retryAfter = null): CrmSyncOutcome
    {
        $attempt = $contactRequest->crm_attempts;

        if ($attempt >= self::MAX_ATTEMPTS) {
            return $this->reject($contactRequest, $claimToken, "retries_exhausted:{$errorCode}");
        }

        $nextAttemptAt = now()->addSeconds(max(self::RETRY_DELAYS[$attempt] ?? self::MAX_RETRY_DELAY, $retryAfter ?? 0));

        $this->releaseClaim($contactRequest, $claimToken, [
            'crm_last_error' => $errorCode,
            'crm_next_attempt_at' => $nextAttemptAt,
        ]);

        Log::warning('Contact request not delivered to StartEntreprise; retry scheduled.', [
            'contact_request_id' => $contactRequest->public_id,
            'attempt' => $attempt,
            'error_code' => $errorCode,
            'next_attempt_at' => $nextAttemptAt->toIso8601String(),
        ]);

        return CrmSyncOutcome::RetryScheduled;
    }

    private function reject(ContactRequest $contactRequest, string $claimToken, string $errorCode): CrmSyncOutcome
    {
        $this->releaseClaim($contactRequest, $claimToken, [
            'crm_status' => DeliveryStatus::Failed,
            'crm_last_error' => $errorCode,
            'crm_next_attempt_at' => null,
        ]);

        Log::error('Contact request rejected by StartEntreprise; investigation required.', [
            'contact_request_id' => $contactRequest->public_id,
            'attempt' => $contactRequest->crm_attempts,
            'error_code' => $errorCode,
        ]);

        return CrmSyncOutcome::Rejected;
    }

    /**
     * Put the request back without counting the attempt and pause the whole synchronization.
     */
    private function pauseFor(ContactRequest $contactRequest, string $claimToken, string $errorCode, int $seconds): CrmSyncOutcome
    {
        $until = CarbonImmutable::now()->addSeconds($seconds);

        $this->releaseClaim($contactRequest, $claimToken, [
            'crm_attempts' => DB::raw('crm_attempts - 1'),
            'crm_last_error' => $errorCode,
            'crm_next_attempt_at' => $until,
        ]);
        Cache::put(self::PAUSE_CACHE_KEY, ['until' => $until->toIso8601String(), 'reason' => $errorCode], $seconds);

        $context = ['error_code' => $errorCode, 'paused_until' => $until->toIso8601String()];

        if (in_array($errorCode, ['RATE_LIMITED'], true)) {
            Log::warning('StartEntreprise CRM synchronization throttled.', $context);
        } else {
            Log::error('StartEntreprise CRM synchronization paused: configuration problem.', $context + [
                'action' => 'Check the integration credentials, scope commercial:leads:write, the API entitlement and the default commercial assignee in StartEntreprise, then run contact-requests:crm-requeue.',
            ]);
        }

        return CrmSyncOutcome::Paused;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function releaseClaim(ContactRequest $contactRequest, string $claimToken, array $values): void
    {
        // Conditional on the claim, so a process whose lease expired cannot overwrite the outcome
        // of the process that took over.
        ContactRequest::query()
            ->whereKey($contactRequest->getKey())
            ->where('crm_claim_token', $claimToken)
            ->update($values + ['crm_claim_token' => null, 'crm_claimed_until' => null]);

        $contactRequest->refresh();
    }

    /**
     * A safe, searchable reason: HTTP status, StartEntreprise's code and the offending field paths
     * (never their values).
     */
    private function rejection(int $status, ?string $code, Response $response): string
    {
        $fields = collect($response->json('fieldErrors') ?? [])
            ->pluck('field')
            ->filter(fn (mixed $field): bool => is_string($field) && preg_match('/^[A-Za-z0-9_.\[\]]{1,80}$/', $field) === 1)
            ->implode(',');

        return Str::limit("http_{$status}".($code === null ? '' : ":{$code}").($fields === '' ? '' : ":{$fields}"), 250, '');
    }

    private function retryAfter(Response $response): ?int
    {
        $header = $response->header('Retry-After');

        if ($header === '') {
            return null;
        }

        $seconds = ctype_digit($header)
            ? (int) $header
            : (int) now()->diffInSeconds(rescue(fn () => CarbonImmutable::parse($header), now(), report: false), false);

        return min(max($seconds, 1), 3600);
    }
}
