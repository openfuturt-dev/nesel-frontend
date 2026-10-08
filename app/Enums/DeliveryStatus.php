<?php

namespace App\Enums;

/**
 * Delivery state of a contact request to one destination (team email, CRM).
 * Pending covers both "not attempted yet" and "failed, will be retried".
 */
enum DeliveryStatus: string
{
    case Pending = 'pending';
    case Delivered = 'delivered';
    case Failed = 'failed';
}
