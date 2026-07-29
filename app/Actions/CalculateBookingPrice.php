<?php

namespace App\Actions;

use App\Data\BookingPriceSnapshot;
use App\Models\Coupon;
use App\Models\Discount;
use App\Models\Event;
use App\Models\EventPriceTier;
use App\Support\Money\MoneyFactory;
use Brick\Money\Money;
use Carbon\CarbonInterface;

class CalculateBookingPrice
{
    public function __construct(private ResolveEventPriceTier $resolveEventPriceTier) {}

    public function execute(Event $event, int $familyMemberCount, ?CarbonInterface $bookedAt = null, ?string $couponCode = null): BookingPriceSnapshot
    {
        $bookedAt ??= now();
        $familyMemberCount = max(0, $familyMemberCount);
        $tier = $this->resolveEventPriceTier->execute($event, max(1, $familyMemberCount));
        $unitPrice = $tier instanceof EventPriceTier ? $tier->price : $event->price;

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
        $coupon = $this->eligibleCoupon($event, $familyMemberCount, $bookedAt, $couponCode);
        $couponAmount = $coupon instanceof Coupon
            ? $coupon->amountForFamilyMembers($familyMemberCount, $subtotal)
            : MoneyFactory::zero($event->currency);
        $couponWins = $coupon instanceof Coupon && $couponAmount->isGreaterThanOrEqualTo($discountAmount);
        $appliedDiscountAmount = $couponWins ? $couponAmount : $discountAmount;

        $total = Money::max(
            MoneyFactory::zero($event->currency),
            $subtotal->minus($appliedDiscountAmount),
        );

        return new BookingPriceSnapshot(
            unitPriceBaisa: MoneyFactory::toMinor($unitPrice),
            currency: $event->currency,
            familyMemberCount: $familyMemberCount,
            subtotalBaisa: MoneyFactory::toMinor($subtotal),
            discountId: ! $couponWins && $discount instanceof Discount ? $discount->id : null,
            couponId: $couponWins ? $coupon->id : null,
            couponCode: $couponWins ? $coupon->code : null,
            discountName: $couponWins ? $coupon->name : ($discount instanceof Discount ? $discount->name : null),
            discountSource: $couponWins ? 'coupon' : ($discount instanceof Discount ? 'discount' : null),
            discountAmountBaisa: MoneyFactory::toMinor($appliedDiscountAmount),
            totalBaisa: MoneyFactory::toMinor($total),
        );
    }

    private function eligibleCoupon(Event $event, int $familyMemberCount, CarbonInterface $bookedAt, ?string $couponCode): ?Coupon
    {
        if (blank($couponCode)) {
            return null;
        }

        return Coupon::query()
            ->matchingCode($couponCode)
            ->eligibleFor($event, $familyMemberCount, $bookedAt)
            ->first();
    }
}
