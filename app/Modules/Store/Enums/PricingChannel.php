<?php

namespace App\Modules\Store\Enums;

enum PricingChannel: string
{
    case Storefront = 'storefront';
    case Api = 'api';
    case Uchat = 'uchat';
}
