<?php

namespace App\Actions;

use App\Enums\DeliveryStatus;
use App\Mail\ContactRequestSubmitted;
use App\Models\ContactRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use Throwable;

/**
 * Emails a stored contact request to the Nesel team, with retries.
 *
 * Each attempt first claims the row with a single conditional UPDATE, so two
 * processes can never send the same request at the same time. Delivery is still
 * at-least-once: if the process dies after the SMTP server accepted the message
 * but before success is recorded, the claim expires and the email is sent again.
 * The stable Message-ID lets clients that de-duplicate on it (Gmail does) hide
 * that copy.
 */
class SendContactRequestNotification
{
    /**
     * Seconds to wait before the next attempt, keyed by attempts already made.
     * The first attempt runs right after the form response.
     *
     * @var array<int, int>
     */
    public const RETRY_DELAYS = [1 => 60, 2 => 300, 3 => 900, 4 => 3600, 5 => 21600];

    /** The first attempt plus one per retry delay. */
    public const MAX_ATTEMPTS = 6;

    /** Lease on a claimed row; far longer than a send bounded by SMTP_TIMEOUT. */
    public const CLAIM_SECONDS = 300;

    /**
     * Attempt delivery if the notification is due and no other process holds it.
     *
     * Returns the resulting status, or null when nothing was attempted. Delivery
     * failures are recorded on the row and logged, never thrown.
     */
    public function handle(ContactRequest $contactRequest): ?DeliveryStatus
    {
        $claimToken = (string) Str::uuid();

        $claimed = $this->due()->whereKey($contactRequest->getKey())->update([
            'notification_claim_token' => $claimToken,
            'notification_claimed_until' => now()->addSeconds(self::CLAIM_SECONDS),
            'notification_attempts' => DB::raw('notification_attempts + 1'),
            'notification_last_attempted_at' => now(),
        ]);

        if ($claimed === 0) {
            return null;
        }

        $contactRequest->refresh();

        try {
            Mail::to((string) config('services.contact.recipient'))->send(
                new ContactRequestSubmitted(
                    name: $contactRequest->name,
                    email: $contactRequest->email,
                    phone: $contactRequest->phone,
                    city: $contactRequest->city,
                    details: $contactRequest->message,
                    offer: $contactRequest->offer,
                    reference: $contactRequest->public_id,
                ),
            );
        } catch (Throwable $exception) {
            return $this->recordFailure($contactRequest, $claimToken, $exception);
        }

        return $this->recordSuccess($contactRequest);
    }

    /**
     * Notifications that may be attempted now: pending, under the attempt limit,
     * past their backoff delay and not claimed by a live process.
     *
     * @return Builder<ContactRequest>
     */
    public function due(): Builder
    {
        $now = now();

        return ContactRequest::query()
            ->where('notification_status', DeliveryStatus::Pending)
            ->where('notification_attempts', '<', self::MAX_ATTEMPTS)
            ->where(fn (Builder $query) => $query
                ->whereNull('notification_next_attempt_at')
                ->orWhere('notification_next_attempt_at', '<=', $now))
            ->where(fn (Builder $query) => $query
                ->whereNull('notification_claimed_until')
                ->orWhere('notification_claimed_until', '<=', $now));
    }

    /**
     * Fail notifications whose last allowed attempt never reported back because
     * the process died while holding the claim.
     */
    public function failAbandoned(): int
    {
        $abandoned = ContactRequest::query()
            ->where('notification_status', DeliveryStatus::Pending)
            ->where('notification_attempts', '>=', self::MAX_ATTEMPTS)
            ->where(fn (Builder $query) => $query
                ->whereNull('notification_claimed_until')
                ->orWhere('notification_claimed_until', '<=', now()))
            ->pluck('public_id', 'id');

        if ($abandoned->isEmpty()) {
            return 0;
        }

        ContactRequest::query()->whereKey($abandoned->keys())->update([
            'notification_status' => DeliveryStatus::Failed,
            'notification_last_error' => 'claim_expired',
            'notification_next_attempt_at' => null,
            'notification_claim_token' => null,
            'notification_claimed_until' => null,
        ]);

        foreach ($abandoned as $publicId) {
            Log::error('Contact request notification failed permanently.', [
                'contact_request_id' => $publicId,
                'attempt' => self::MAX_ATTEMPTS,
                'error_code' => 'claim_expired',
            ]);
        }

        return $abandoned->count();
    }

    private function recordSuccess(ContactRequest $contactRequest): DeliveryStatus
    {
        // Not conditional on the claim: the email went out, whatever else happened.
        ContactRequest::query()->whereKey($contactRequest->getKey())->update([
            'notification_status' => DeliveryStatus::Delivered,
            'notification_sent_at' => now(),
            'notification_last_error' => null,
            'notification_next_attempt_at' => null,
            'notification_claim_token' => null,
            'notification_claimed_until' => null,
        ]);

        Log::info('Contact request notification delivered.', [
            'contact_request_id' => $contactRequest->public_id,
            'attempt' => $contactRequest->notification_attempts,
        ]);

        $contactRequest->refresh();

        return DeliveryStatus::Delivered;
    }

    private function recordFailure(ContactRequest $contactRequest, string $claimToken, Throwable $exception): DeliveryStatus
    {
        [$errorCode, $retryable] = $this->classify($exception);
        $attempt = $contactRequest->notification_attempts;
        $nextAttemptAt = $retryable && $attempt < self::MAX_ATTEMPTS
            ? now()->addSeconds(self::RETRY_DELAYS[$attempt])
            : null;
        $status = $nextAttemptAt ? DeliveryStatus::Pending : DeliveryStatus::Failed;

        // Conditional on the claim, so a process whose lease expired cannot
        // overwrite the outcome of the process that took over.
        $recorded = ContactRequest::query()
            ->whereKey($contactRequest->getKey())
            ->where('notification_claim_token', $claimToken)
            ->update([
                'notification_status' => $status,
                'notification_last_error' => $errorCode,
                'notification_next_attempt_at' => $nextAttemptAt,
                'notification_claim_token' => null,
                'notification_claimed_until' => null,
            ]);

        // Only identifiers and codes: exception messages can contain personal data.
        $context = [
            'contact_request_id' => $contactRequest->public_id,
            'attempt' => $attempt,
            'error_code' => $errorCode,
            'exception' => class_basename($exception),
            'next_attempt_at' => $nextAttemptAt?->toIso8601String(),
            'claim_lost' => $recorded === 0,
        ];

        if ($status === DeliveryStatus::Failed) {
            Log::error('Contact request notification failed permanently.', $context);
        } else {
            Log::warning('Contact request notification failed; retry scheduled.', $context);
        }

        $contactRequest->refresh();

        return $status;
    }

    /**
     * Map a delivery exception to a safe error code, and whether retrying can help.
     *
     * Only a problem with the stored data is permanent; server-side problems
     * (outage, timeout, authentication, rejection) can be fixed by an operator.
     *
     * @return array{0: string, 1: bool}
     */
    private function classify(Throwable $exception): array
    {
        return match (true) {
            $exception instanceof RfcComplianceException => ['invalid_address', false],
            $exception instanceof TransportExceptionInterface => [$this->smtpErrorCode($exception), true],
            default => ['unexpected_error', true],
        };
    }

    private function smtpErrorCode(TransportExceptionInterface $exception): string
    {
        $code = $exception->getCode();

        return $code >= 400 && $code < 600 ? "smtp_{$code}" : 'smtp_unavailable';
    }
}
