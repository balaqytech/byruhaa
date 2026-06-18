<?php

namespace App\Actions;

use App\Data\BookingPriceSnapshot;
use App\Models\Discount;
use App\Models\Event;
use Carbon\CarbonInterface;

class CalculateBookingPrice
{
    public function execute(Event $event, int $familyMemberCount, ?CarbonInterface $bookedAt = null): BookingPriceSnapshot
    {
        $bookedAt ??= now();
        $familyMemberCount = max(0, $familyMemberCount);
        $unitPriceBaisa = max(0, $event->price_baisa);
        $subtotalBaisa = $unitPriceBaisa * $familyMemberCount;

        $discount = Discount::query()
            ->eligibleFor($event, $familyMemberCount, $bookedAt)
            ->orderByDesc('amount_baisa')
            ->orderBy('id')
            ->first();

        $discountAmountBaisa = $discount instanceof Discount
            ? min($subtotalBaisa, $discount->amount_baisa)
            : 0;

        return new BookingPriceSnapshot(
            unitPriceBaisa: $unitPriceBaisa,
            currency: $event->currency,
            familyMemberCount: $familyMemberCount,
            subtotalBaisa: $subtotalBaisa,
            discountId: $discount instanceof Discount ? $discount->id : null,
            discountName: $discount instanceof Discount ? $discount->name : null,
            discountAmountBaisa: $discountAmountBaisa,
            totalBaisa: $subtotalBaisa - $discountAmountBaisa,
        );
    }
}
