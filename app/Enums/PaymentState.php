<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentState: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';

    public function getLabel(): string
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

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Failed => 'danger',
            self::Cancelled => 'gray',
            self::PartiallyRefunded => 'info',
            self::Refunded => 'gray',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
