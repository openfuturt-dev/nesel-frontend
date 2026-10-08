<?php

namespace App\Enums;

/**
 * Result of one attempt to send a contact request to the StartEntreprise CRM.
 */
enum CrmSyncOutcome: string
{
    case Delivered = 'delivered';
    /** StartEntreprise refused this request (400, 409, 413…): kept as failed for investigation. */
    case Rejected = 'rejected';
    /** Temporary failure (5xx, network, timeout): this request retries later with backoff. */
    case RetryScheduled = 'retry_scheduled';
    /** Configuration or throttling problem affecting every request: synchronization is paused. */
    case Paused = 'paused';
    /** Not due, already claimed by another process, or already delivered. */
    case Skipped = 'skipped';

    /**
     * Whether the rest of the batch should wait: the next request would most likely fail the
     * same way, so continuing would only hammer StartEntreprise.
     */
    public function stopsBatch(): bool
    {
        return $this === self::RetryScheduled || $this === self::Paused;
    }
}
