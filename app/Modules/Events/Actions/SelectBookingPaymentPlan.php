<?php

namespace App\Modules\Events\Actions;

use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\BookingPaymentSchedule;
use App\Modules\Events\Models\EventPaymentPlan;
use App\Modules\Events\States\Booking\Approved;
use Brick\Money\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SelectBookingPaymentPlan
{
    public function execute(Booking $booking, EventPaymentPlan $paymentPlan): BookingPaymentSchedule
    {
        return DB::transaction(function () use ($booking, $paymentPlan): BookingPaymentSchedule {
            $booking = Booking::query()
                ->whereKey($booking->id)
                ->with(['customer', 'familyMembers.contract', 'paymentSchedule'])
                ->lockForUpdate()
                ->firstOrFail();

            $booking->customer->ensureProfileIsComplete('paymentPlanId');

            $paymentPlan = EventPaymentPlan::query()
                ->whereKey($paymentPlan->id)
                ->where('event_id', $booking->event_id)
                ->where('is_active', true)
                ->with('installments')
                ->firstOrFail();

            if (! $booking->state instanceof Approved) {
                throw ValidationException::withMessages([
                    'paymentPlanId' => __('ui.messages.booking_must_be_approved_for_payment_plan'),
                ]);
            }

            if (! $booking->hasSignedContracts()) {
                throw ValidationException::withMessages([
                    'paymentPlanId' => __('ui.messages.contracts_must_be_signed_for_payment_plan'),
                ]);
            }

            if ($booking->paymentSchedule) {
                throw ValidationException::withMessages([
                    'paymentPlanId' => __('ui.messages.payment_plan_already_selected'),
                ]);
            }

            if ($booking->total_baisa === 0) {
                throw ValidationException::withMessages([
                    'paymentPlanId' => __('ui.messages.payment_plan_not_required_for_free_booking'),
                ]);
            }

            if ($paymentPlan->installments->isEmpty() || $paymentPlan->installments->sum('percentage') !== 100) {
                throw ValidationException::withMessages([
                    'paymentPlanId' => __('ui.messages.payment_plan_percentages_invalid'),
                ]);
            }

            $percentages = $paymentPlan->installments->pluck('percentage')->all();
            $grossAmounts = $this->allocateRemainderToLast($booking->subtotal, $percentages);
            $discountAmounts = $this->allocateRemainderToLast($booking->discount_amount, $percentages);

            $schedule = $booking->paymentSchedule()->create([
                'event_payment_plan_id' => $paymentPlan->id,
                'plan_name' => $paymentPlan->name,
                'currency' => $booking->currency,
                'subtotal' => $booking->subtotal,
                'discount_amount' => $booking->discount_amount,
                'total' => $booking->total,
            ]);

            foreach ($paymentPlan->installments->values() as $index => $planInstallment) {
                $grossAmount = $grossAmounts[$index];
                $discountAmount = $discountAmounts[$index];
                $amount = $grossAmount->minus($discountAmount);

                $schedule->installments()->create([
                    'name' => $planInstallment->name,
                    'sequence' => $planInstallment->sequence,
                    'percentage' => $planInstallment->percentage,
                    'due_date' => $planInstallment->due_date,
                    'gross_amount' => $grossAmount,
                    'discount_amount' => $discountAmount,
                    'amount' => $amount,
                    'currency' => $booking->currency,
                ]);
            }

            return $schedule->load('installments');
        });
    }

    /**
     * @param  array<int, int>  $percentages
     * @return array<int, Money>
     */
    private function allocateRemainderToLast(Money $amount, array $percentages): array
    {
        return array_reverse($amount->allocate(...array_reverse($percentages)));
    }
}
