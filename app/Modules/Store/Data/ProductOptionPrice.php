<?php

namespace App\Modules\Store\Data;

use App\Modules\Store\Enums\PricingTier;
use LogicException;

final readonly class ProductOptionPrice
{
    /** @param list<PriceAdjustment> $adjustments */
    public function __construct(
        public int $regularPriceBaisa,
        public ?int $memberPriceBaisa,
        public int $effectivePriceBaisa,
        public int $unitDiscountBaisa,
        public PricingTier $pricingTier,
        public array $adjustments = [],
    ) {
        $adjustmentTotal = array_sum(array_map(
            fn (PriceAdjustment $adjustment): int => $adjustment->unitAmountBaisa,
            $this->adjustments,
        ));

        if ($this->effectivePriceBaisa < 0
            || $this->effectivePriceBaisa > $this->regularPriceBaisa
            || $this->unitDiscountBaisa !== $this->regularPriceBaisa - $this->effectivePriceBaisa
            || $adjustmentTotal !== $this->unitDiscountBaisa) {
            throw new LogicException('The product option pricing result is internally inconsistent.');
        }
    }

    /** @return array{regular_price_baisa: int, member_price_baisa: int|null, effective_price_baisa: int, unit_discount_baisa: int, pricing_tier: string, adjustments: list<array<string, mixed>>} */
    public function toArray(): array
    {
        return [
            'regular_price_baisa' => $this->regularPriceBaisa,
            'member_price_baisa' => $this->memberPriceBaisa,
            'effective_price_baisa' => $this->effectivePriceBaisa,
            'unit_discount_baisa' => $this->unitDiscountBaisa,
            'pricing_tier' => $this->pricingTier->value,
            'adjustments' => array_map(
                fn (PriceAdjustment $adjustment): array => $adjustment->toArray(),
                $this->adjustments,
            ),
        ];
    }
}
