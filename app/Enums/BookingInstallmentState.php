<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BookingInstallmentState: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Paid = 'paid';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => __('admin.statuses.pending'),
            self::Paid => __('admin.statuses.paid'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
