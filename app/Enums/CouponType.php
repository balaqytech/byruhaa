<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CouponType: string implements HasLabel
{
    case FixedAmountPerMember = 'fixed_amount_per_member';
    case PercentagePerMember = 'percentage_per_member';

    public function getLabel(): string
    {
        return match ($this) {
            self::FixedAmountPerMember => __('admin.coupon_types.fixed_amount_per_member'),
            self::PercentagePerMember => __('admin.coupon_types.percentage_per_member'),
        };
    }
}
