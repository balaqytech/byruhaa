<?php

namespace App\Modules\Store\Services;

use App\Modules\Store\Contracts\ProductOptionPricingRule;
use App\Modules\Store\Data\PricingContext;
use App\Modules\Store\Data\ProductOptionPrice;
use App\Modules\Store\Enums\PricingChannel;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\ProductOption;
use LogicException;

class StorePricing
{
    /** @var list<ProductOptionPricingRule> */
    private array $rules;

    /** @param iterable<ProductOptionPricingRule> $rules */
    public function __construct(
        private PricingContextResolver $contexts,
        iterable $rules,
    ) {
        $this->rules = array_values(collect($rules)
            ->sortBy(fn (ProductOptionPricingRule $rule): int => $rule->priority())
            ->values()
            ->all());
    }

    public function forCart(
        Cart $cart,
        ProductOption $option,
        PricingChannel $channel = PricingChannel::Storefront,
    ): ProductOptionPrice {
        return $this->forOption($option, $this->contexts->forCart($cart, $channel));
    }

    public function forOption(ProductOption $option, PricingContext $context): ProductOptionPrice
    {
        $regularPriceBaisa = $option->price_baisa;
        $effectivePriceBaisa = $regularPriceBaisa;
        $adjustments = [];

        foreach ($this->rules as $rule) {
            $adjustment = $rule->evaluate($option, $context, $effectivePriceBaisa);

            if ($adjustment === null) {
                continue;
            }

            if ($adjustment->unitAmountBaisa > $effectivePriceBaisa) {
                throw new LogicException('A pricing rule cannot reduce an option below zero.');
            }

            $adjustments[] = $adjustment;
            $effectivePriceBaisa -= $adjustment->unitAmountBaisa;

            if (! $adjustment->stackable) {
                break;
            }
        }

        return new ProductOptionPrice(
            regularPriceBaisa: $regularPriceBaisa,
            memberPriceBaisa: $option->member_price_baisa,
            effectivePriceBaisa: $effectivePriceBaisa,
            unitDiscountBaisa: $regularPriceBaisa - $effectivePriceBaisa,
            pricingTier: $context->tier,
            adjustments: $adjustments,
        );
    }
}
