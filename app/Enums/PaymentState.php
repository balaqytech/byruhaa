<?php

namespace App\Enums;

enum PaymentState: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('admin.statuses.pending'),
            self::Paid => __('admin.statuses.paid'),
            self::Failed => __('admin.statuses.failed'),
            self::Cancelled => __('admin.statuses.cancelled'),
            self::PartiallyRefunded => __('admin.statuses.partially_refunded'),
            self::Refunded => __('admin.statuses.refunded'),
        };
    }
}
