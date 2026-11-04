<?php

namespace App\Modules\Store\Enums;

enum PriceAdjustmentType: string
{
    case MemberPrice = 'member_price';
    case Promotion = 'promotion';
    case Coupon = 'coupon';
}
