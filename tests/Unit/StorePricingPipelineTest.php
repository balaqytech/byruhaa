<?php

use App\Modules\Store\Contracts\ProductOptionPricingRule;
use App\Modules\Store\Data\PriceAdjustment;
use App\Modules\Store\Data\PricingContext;
use App\Modules\Store\Enums\PriceAdjustmentType;
use App\Modules\Store\Enums\PricingChannel;
use App\Modules\Store\Models\ProductOption;
use App\Modules\Store\Services\PricingContextResolver;
use App\Modules\Store\Services\PricingRules\MemberPriceRule;
use App\Modules\Store\Services\StorePricing;

function pipelineProductOption(int $regularPrice = 1600, ?int $memberPrice = 1100): ProductOption
{
    return (new ProductOption)->forceFill([
        'id' => 42,
        'price_baisa' => $regularPrice,
        'member_price_baisa' => $memberPrice,
    ]);
}

test('the member rule leaves standard pricing unchanged', function (): void {
    $contexts = new PricingContextResolver;
    $pricing = new StorePricing($contexts, [new MemberPriceRule]);

    $result = $pricing->forOption(
        pipelineProductOption(),
        $contexts->forIdentity(null, channel: PricingChannel::Api),
    );

    expect($result)
        ->regularPriceBaisa->toBe(1600)
        ->effectivePriceBaisa->toBe(1600)
        ->unitDiscountBaisa->toBe(0)
        ->adjustments->toBe([])
        ->and($result->toArray()['pricing_tier'])->toBe('standard');
});

test('the member rule produces a traceable price adjustment', function (): void {
    $contexts = new PricingContextResolver;
    $pricing = new StorePricing($contexts, [new MemberPriceRule]);

    $result = $pricing->forOption(
        pipelineProductOption(),
        $contexts->forIdentity(7, channel: PricingChannel::Storefront),
    );

    expect($result)
        ->regularPriceBaisa->toBe(1600)
        ->effectivePriceBaisa->toBe(1100)
        ->unitDiscountBaisa->toBe(500)
        ->and($result->adjustments)->toHaveCount(1)
        ->and($result->adjustments[0]->type)->toBe(PriceAdjustmentType::MemberPrice)
        ->and($result->adjustments[0]->unitAmountBaisa)->toBe(500)
        ->and($result->toArray()['pricing_tier'])->toBe('member');
});

test('pricing rules are evaluated deterministically by priority', function (): void {
    $contexts = new PricingContextResolver;
    $lowerPriorityNumber = new class implements ProductOptionPricingRule
    {
        public function priority(): int
        {
            return 10;
        }

        public function evaluate(ProductOption $option, PricingContext $context, int $currentUnitPriceBaisa): ?PriceAdjustment
        {
            return new PriceAdjustment(
                type: PriceAdjustmentType::Promotion,
                label: 'first',
                unitAmountBaisa: 100,
                sourceType: 'test',
                priority: $this->priority(),
            );
        }
    };
    $higherPriorityNumber = new class implements ProductOptionPricingRule
    {
        public function priority(): int
        {
            return 20;
        }

        public function evaluate(ProductOption $option, PricingContext $context, int $currentUnitPriceBaisa): ?PriceAdjustment
        {
            expect($currentUnitPriceBaisa)->toBe(1500);

            return new PriceAdjustment(
                type: PriceAdjustmentType::Coupon,
                label: 'second',
                unitAmountBaisa: 200,
                sourceType: 'test',
                priority: $this->priority(),
            );
        }
    };
    $pricing = new StorePricing($contexts, [$higherPriorityNumber, $lowerPriorityNumber]);

    $result = $pricing->forOption(pipelineProductOption(memberPrice: null), $contexts->forIdentity(null));

    expect($result)
        ->effectivePriceBaisa->toBe(1300)
        ->unitDiscountBaisa->toBe(300)
        ->and(array_column($result->toArray()['adjustments'], 'label'))->toBe(['first', 'second']);
});

test('a pricing rule cannot reduce a product option below zero', function (): void {
    $contexts = new PricingContextResolver;
    $invalidRule = new class implements ProductOptionPricingRule
    {
        public function priority(): int
        {
            return 1;
        }

        public function evaluate(ProductOption $option, PricingContext $context, int $currentUnitPriceBaisa): ?PriceAdjustment
        {
            return new PriceAdjustment(
                type: PriceAdjustmentType::Promotion,
                label: 'invalid',
                unitAmountBaisa: $currentUnitPriceBaisa + 1,
                sourceType: 'test',
            );
        }
    };
    $pricing = new StorePricing($contexts, [$invalidRule]);

    expect(fn () => $pricing->forOption(pipelineProductOption(), $contexts->forIdentity(null)))
        ->toThrow(LogicException::class, 'below zero');
});
