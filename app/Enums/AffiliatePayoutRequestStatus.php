<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AffiliatePayoutRequestStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Paid = 'paid';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => __('admin.statuses.pending'),
            self::Approved => __('admin.statuses.approved'),
            self::Paid => __('admin.statuses.paid'),
            self::Rejected => __('admin.statuses.rejected'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'info',
            self::Paid => 'success',
            self::Rejected => 'danger',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
