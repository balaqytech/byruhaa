<?php

namespace App\Data;

class BookingPriceSnapshot
{
    public function __construct(
        public readonly int $unitPriceBaisa,
        public readonly string $currency,
        public readonly int $familyMemberCount,
        public readonly int $subtotalBaisa,
        public readonly ?int $discountId,
        public readonly ?int $couponId,
        public readonly ?string $couponCode,
        public readonly ?string $discountName,
        public readonly ?string $discountSource,
        public readonly int $discountAmountBaisa,
        public readonly int $totalBaisa,
    ) {}

    /**
     * @return array<string, int|string|null>
     */
    public function toBookingAttributes(): array
    {
        return [
            'unit_price_baisa' => $this->unitPriceBaisa,
            'currency' => $this->currency,
            'family_member_count' => $this->familyMemberCount,
            'subtotal_baisa' => $this->subtotalBaisa,
            'discount_id' => $this->discountId,
            'coupon_id' => $this->couponId,
            'coupon_code' => $this->couponCode,
            'discount_name' => $this->discountName,
            'discount_amount_baisa' => $this->discountAmountBaisa,
            'total_baisa' => $this->totalBaisa,
        ];
    }
}
