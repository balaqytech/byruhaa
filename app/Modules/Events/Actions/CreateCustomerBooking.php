<?php

namespace App\Modules\Events\Actions;

use App\Enums\AffiliateStatus;
use App\Enums\EventInterestStatus;
use App\Enums\EventStatus;
use App\Modules\Affiliates\Models\Affiliate;
use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\Coupon;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Services\BookingApprovalService;
use App\Modules\Events\Services\CouponUsageService;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Services\Webhooks\ByruhaaWebhookSender;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateCustomerBooking
{
    public function __construct(
        private CalculateBookingPrice $calculateBookingPrice,
        private ByruhaaWebhookSender $webhookSender,
        private BookingApprovalService $bookingApprovalService,
        private CouponUsageService $couponUsageService,
    ) {}

    /**
     * @param  array{event_id: int, family_member_ids: array<int, int>, coupon_code?: string|null}  $data
     * @param  array{affiliate_id: int, code: string, name: string, captured_at: string, expires_at: string}|null  $affiliateAttribution
     */
    public function execute(Customer $customer, array $data, ?array $affiliateAttribution = null): Booking
    {
        $customer->ensureProfileIsComplete('family_member_ids');

        $booking = DB::transaction(function () use ($customer, $data, $affiliateAttribution): Booking {
            $event = Event::query()
                ->whereKey($data['event_id'])
                ->where('status', EventStatus::Published)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $event->canBook()) {
                throw ValidationException::withMessages([
                    'event_id' => 'الحجز غير مفتوح لهذه الفعالية حاليًا.',
                ]);
            }

            $familyMemberIds = array_values(array_unique($data['family_member_ids']));
            $familyMembers = $this->familyMembers($customer, $familyMemberIds);

            $this->validateFamilyMembers($event, $familyMembers, count($familyMemberIds));

            $bookedAt = now();
            $couponCode = $this->couponCode($data);
            $priceSnapshot = $this->calculateBookingPrice->execute($event, $familyMembers->count(), $bookedAt, $couponCode);

            if ($couponCode !== null && ! $this->hasEligibleCoupon($event, $familyMembers->count(), $bookedAt, $couponCode)) {
                throw ValidationException::withMessages([
                    'coupon_code' => __('ui.messages.invalid_coupon_code'),
                ]);
            }

            $booking = Booking::create([
                'customer_id' => $customer->id,
                'event_id' => $event->id,
                ...$priceSnapshot->toBookingAttributes(),
            ]);

            $customer->eventInterests()
                ->where('event_id', $event->id)
                ->where('status', EventInterestStatus::Interested->value)
                ->update([
                    'status' => EventInterestStatus::BookingStarted->value,
                    'booking_id' => $booking->id,
                ]);

            if ($priceSnapshot->couponId !== null) {
                $coupon = Coupon::query()->findOrFail($priceSnapshot->couponId);

                $this->couponUsageService->redeemForBooking($coupon, $booking);
            }

            foreach ($familyMembers as $familyMember) {
                $booking->familyMembers()->create([
                    'family_member_id' => $familyMember->id,
                ]);
            }

            $this->createAffiliateReferral($booking, $affiliateAttribution);

            return $booking->load(['event', 'familyMembers.familyMember', 'paymentSchedule.installments']);
        });

        $this->webhookSender->sendBookingCreated($booking);

        if ($this->usesAutomaticApproval()) {
            return $this->bookingApprovalService
                ->approve($booking)
                ->load(['event', 'familyMembers.familyMember', 'paymentSchedule.installments']);
        }

        return $booking;
    }

    /**
     * @param  array{coupon_code?: string|null}  $data
     */
    private function couponCode(array $data): ?string
    {
        $couponCode = $data['coupon_code'] ?? null;

        if (! is_string($couponCode) || blank($couponCode)) {
            return null;
        }

        return Coupon::normalizeCode($couponCode);
    }

    private function hasEligibleCoupon(Event $event, int $familyMemberCount, CarbonInterface $bookedAt, string $couponCode): bool
    {
        return Coupon::query()
            ->matchingCode($couponCode)
            ->eligibleFor($event, $familyMemberCount, $bookedAt)
            ->exists();
    }

    private function usesAutomaticApproval(): bool
    {
        return str(config('byruhaa.approval_mechanism', 'manual'))
            ->trim()
            ->lower()
            ->toString() === 'auto';
    }

    /**
     * @param  array{affiliate_id: int, code: string, name: string, captured_at: string, expires_at: string}|null  $affiliateAttribution
     */
    private function createAffiliateReferral(Booking $booking, ?array $affiliateAttribution): void
    {
        if ($affiliateAttribution === null) {
            return;
        }

        if (now()->greaterThanOrEqualTo(Carbon::parse($affiliateAttribution['expires_at']))) {
            return;
        }

        $affiliate = Affiliate::query()
            ->whereKey($affiliateAttribution['affiliate_id'])
            ->where('status', AffiliateStatus::Approved->value)
            ->first();

        if (! $affiliate instanceof Affiliate) {
            return;
        }

        $booking->affiliateReferral()->create([
            'affiliate_id' => $affiliate->id,
            'affiliate_code' => $affiliateAttribution['code'],
            'affiliate_name' => $affiliateAttribution['name'],
            'captured_at' => $affiliateAttribution['captured_at'],
            'expires_at' => $affiliateAttribution['expires_at'],
            'attributed_at' => now(),
        ]);
    }

    /**
     * @param  array<int, int>  $familyMemberIds
     * @return Collection<int, FamilyMember>
     */
    private function familyMembers(Customer $customer, array $familyMemberIds): Collection
    {
        return FamilyMember::query()
            ->whereBelongsTo($customer)
            ->whereIn('id', $familyMemberIds)
            ->get();
    }

    /**
     * @param  Collection<int, FamilyMember>  $familyMembers
     */
    private function validateFamilyMembers(Event $event, Collection $familyMembers, int $expectedFamilyMemberCount): void
    {
        if ($familyMembers->count() !== $expectedFamilyMemberCount) {
            throw ValidationException::withMessages([
                'family_member_ids' => __('ui.messages.invalid_family_member_selection'),
            ]);
        }

        foreach ($familyMembers as $familyMember) {
            $age = $familyMember->ageAt($event->starts_at ?? now());

            if ($age < $event->minimum_age || $age > $event->maximum_age) {
                throw ValidationException::withMessages([
                    'family_member_ids' => __('ui.messages.all_family_members_age_range'),
                ]);
            }
        }

        if ($familyMembers->count() > $event->remainingSeats()) {
            throw ValidationException::withMessages([
                'family_member_ids' => __('ui.messages.not_enough_seats'),
            ]);
        }
    }
}
