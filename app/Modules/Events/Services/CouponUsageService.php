<?php

namespace App\Modules\Events\Services;

use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\Coupon;
use Illuminate\Validation\ValidationException;

class CouponUsageService
{
    public function redeemForBooking(Coupon $coupon, Booking $booking): void
    {
        $coupon = Coupon::query()
            ->whereKey($coupon->id)
            ->lockForUpdate()
            ->firstOrFail();

        if (! $this->hasCapacity($coupon, $booking->customer_id)) {
            throw ValidationException::withMessages([
                'coupon_code' => __('ui.messages.coupon_usage_limit_reached'),
            ]);
        }

        $booking->couponRedemption()->firstOrCreate([], [
            'coupon_id' => $coupon->id,
            'customer_id' => $booking->customer_id,
            'redeemed_at' => now(),
        ]);
    }

    public function releaseForBooking(Booking $booking): void
    {
        $redemption = $booking->couponRedemption()
            ->whereNull('released_at')
            ->first();

        if ($redemption === null) {
            return;
        }

        $redemption->forceFill([
            'released_at' => now(),
        ])->save();
    }

    public function hasCapacity(Coupon $coupon, int $customerId): bool
    {
        if ($coupon->maximum_uses !== null && $coupon->activeRedemptionsCount() >= $coupon->maximum_uses) {
            return false;
        }

        if ($coupon->maximum_uses_per_customer !== null && $coupon->activeRedemptionsCountForCustomer($customerId) >= $coupon->maximum_uses_per_customer) {
            return false;
        }

        return true;
    }
}
