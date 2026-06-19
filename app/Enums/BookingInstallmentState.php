<?php

namespace App\Enums;

enum BookingInstallmentState: string
{
    case Pending = 'pending';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('admin.statuses.pending'),
            self::Paid => __('admin.statuses.paid'),
        };
    }
}
