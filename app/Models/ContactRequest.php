<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use Database\Factories\ContactRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'submission_token', 'name', 'email', 'phone', 'city', 'offer', 'message',
    'landing_page_url', 'referrer_url', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
])]
class ContactRequest extends Model
{
    /** @use HasFactory<ContactRequestFactory> */
    use HasFactory, HasUlids;

    /**
     * Mirror the column defaults so freshly created instances are complete.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'notification_status' => 'pending',
        'notification_attempts' => 0,
        'crm_status' => 'pending',
        'crm_attempts' => 0,
    ];

    /**
     * Keep the auto-incrementing key; the ULID is the permanent public identifier.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'notification_status' => DeliveryStatus::class,
            'notification_last_attempted_at' => 'datetime',
            'notification_next_attempt_at' => 'datetime',
            'notification_claimed_until' => 'datetime',
            'notification_sent_at' => 'datetime',
            'crm_status' => DeliveryStatus::class,
            'crm_last_attempted_at' => 'datetime',
            'crm_next_attempt_at' => 'datetime',
            'crm_claimed_until' => 'datetime',
            'crm_backfill_requested_at' => 'datetime',
            'crm_delivered_at' => 'datetime',
        ];
    }
}
