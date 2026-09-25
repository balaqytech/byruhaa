<?php

namespace App\Modules\Store\Services\PricingRules;

use App\Modules\Store\Contracts\ProductOptionPricingRule;
use App\Modules\Store\Data\PriceAdjustment;
use App\Modules\Store\Data\PricingContext;
use App\Modules\Store\Enums\PriceAdjustmentType;
use App\Modules\Store\Models\ProductOption;

class MemberPriceRule implements ProductOptionPricingRule
{
    public function priority(): int
    {
        return 100;
    }

    public function evaluate(ProductOption $option, PricingContext $context, int $currentUnitPriceBaisa): ?PriceAdjustment
    {
        if (! $context->isMember() || $option->member_price_baisa === null) {
            return null;
        }

        $amountBaisa = $currentUnitPriceBaisa - $option->member_price_baisa;

        if ($amountBaisa <= 0) {
            return null;
        }

        return new PriceAdjustment(
            type: PriceAdjustmentType::MemberPrice,
            label: 'member_price',
            unitAmountBaisa: $amountBaisa,
            sourceType: 'product_option',
            sourceId: $option->getKey(),
            priority: $this->priority(),
        );
    }
}
