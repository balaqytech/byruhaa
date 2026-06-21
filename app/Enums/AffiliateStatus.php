<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AffiliateStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Suspended = 'suspended';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => __('admin.statuses.pending'),
            self::Approved => __('admin.statuses.approved'),
            self::Rejected => __('admin.statuses.rejected'),
            self::Suspended => __('admin.statuses.suspended'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Suspended => 'gray',
        };
    }

    public function label(): string
    {
        return $this->getLabel();
    }
}
