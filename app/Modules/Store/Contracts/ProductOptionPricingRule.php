<?php

namespace App\Modules\Store\Contracts;

use App\Modules\Store\Data\PriceAdjustment;
use App\Modules\Store\Data\PricingContext;
use App\Modules\Store\Models\ProductOption;

interface ProductOptionPricingRule
{
    public function priority(): int;

    public function evaluate(ProductOption $option, PricingContext $context, int $currentUnitPriceBaisa): ?PriceAdjustment;
}
