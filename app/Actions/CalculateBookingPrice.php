<?php

namespace App\Actions;

use App\Data\BookingPriceSnapshot;
use App\Models\Discount;
use App\Models\Event;
use App\Support\Money\MoneyFactory;
use Brick\Money\Money;
use Carbon\CarbonInterface;

class CalculateBookingPrice
{
    public function execute(Event $event, int $familyMemberCount, ?CarbonInterface $bookedAt = null): BookingPriceSnapshot
    {
        $bookedAt ??= now();
        $familyMemberCount = max(0, $familyMemberCount);
        $unitPrice = $event->price;

        if ($unitPrice->isNegative()) {
            $unitPrice = MoneyFactory::zero($event->currency);
        }

        $subtotal = $unitPrice->multipliedBy($familyMemberCount);

        $discount = Discount::query()
            ->eligibleFor($event, $familyMemberCount, $bookedAt)
            ->orderByDesc('amount_baisa')
            ->orderBy('id')
            ->first();

        $discountAmount = $discount instanceof Discount
            ? $discount->amountForFamilyMembers($familyMemberCount, $subtotal)
            : MoneyFactory::zero($event->currency);

        $total = Money::max(
            MoneyFactory::zero($event->currency),
            $subtotal->minus($discountAmount),
        );

        return new BookingPriceSnapshot(
            unitPriceBaisa: MoneyFactory::toMinor($unitPrice),
            currency: $event->currency,
            familyMemberCount: $familyMemberCount,
            subtotalBaisa: MoneyFactory::toMinor($subtotal),
            discountId: $discount instanceof Discount ? $discount->id : null,
            discountName: $discount instanceof Discount ? $discount->name : null,
            discountAmountBaisa: MoneyFactory::toMinor($discountAmount),
            totalBaisa: MoneyFactory::toMinor($total),
        );
    }
}
