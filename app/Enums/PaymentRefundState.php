<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentRefundState: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case ManualRequired = 'manual_required';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => __('admin.statuses.pending'),
            self::Succeeded => __('admin.statuses.succeeded'),
            self::Failed => __('admin.statuses.failed'),
            self::ManualRequired => 'يتطلب استردادًا يدويًا',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Succeeded => 'success',
            self::Failed => 'danger',
            self::ManualRequired => 'warning',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
