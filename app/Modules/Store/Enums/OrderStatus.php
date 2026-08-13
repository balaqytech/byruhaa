<?php

namespace App\Modules\Store\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Confirmed = 'confirmed';
    case Accepted = 'accepted';
    case Preparing = 'preparing';
    case ReadyForPickup = 'ready_for_pickup';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case RefundPending = 'refund_pending';
    case Refunded = 'refunded';
}
