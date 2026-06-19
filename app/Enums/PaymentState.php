<?php

namespace App\Enums;

enum PaymentState: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('admin.statuses.pending'),
            self::Paid => __('admin.statuses.paid'),
            self::Failed => __('admin.statuses.failed'),
            self::Cancelled => __('admin.statuses.cancelled'),
        };
    }
}
