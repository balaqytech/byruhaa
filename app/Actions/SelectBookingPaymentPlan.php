<?php

namespace App\Actions;

use App\Models\Booking;
use App\Models\BookingPaymentSchedule;
use App\Models\EventPaymentPlan;
use App\States\Booking\Approved;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SelectBookingPaymentPlan
{
    public function execute(Booking $booking, EventPaymentPlan $paymentPlan): BookingPaymentSchedule
    {
        return DB::transaction(function () use ($booking, $paymentPlan): BookingPaymentSchedule {
            $booking = Booking::query()
                ->whereKey($booking->id)
                ->with(['familyMembers.contract', 'paymentSchedule'])
                ->lockForUpdate()
                ->firstOrFail();

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

            if ($paymentPlan->installments->isEmpty() || $paymentPlan->installments->sum('percentage') !== 100) {
                throw ValidationException::withMessages([
                    'paymentPlanId' => __('ui.messages.payment_plan_percentages_invalid'),
                ]);
            }

            $grossAmounts = $this->allocateAmount($booking->subtotal_baisa, $paymentPlan->installments->pluck('percentage')->all());
            $discountAmounts = $this->allocateAmount($booking->discount_amount_baisa, $paymentPlan->installments->pluck('percentage')->all());

            $schedule = $booking->paymentSchedule()->create([
                'event_payment_plan_id' => $paymentPlan->id,
                'plan_name' => $paymentPlan->name,
                'currency' => $booking->currency,
                'subtotal_baisa' => $booking->subtotal_baisa,
                'discount_amount_baisa' => $booking->discount_amount_baisa,
                'total_baisa' => $booking->total_baisa,
            ]);

            foreach ($paymentPlan->installments->values() as $index => $planInstallment) {
                $grossAmountBaisa = $grossAmounts[$index];
                $discountAmountBaisa = $discountAmounts[$index];

                $schedule->installments()->create([
                    'name' => $planInstallment->name,
                    'sequence' => $planInstallment->sequence,
                    'percentage' => $planInstallment->percentage,
                    'due_date' => $planInstallment->due_date,
                    'gross_amount_baisa' => $grossAmountBaisa,
                    'discount_amount_baisa' => $discountAmountBaisa,
                    'amount_baisa' => $grossAmountBaisa - $discountAmountBaisa,
                ]);
            }

            return $schedule->load('installments');
        });
    }

    /**
     * @param  array<int, int>  $percentages
     * @return array<int, int>
     */
    private function allocateAmount(int $amountBaisa, array $percentages): array
    {
        $allocated = [];
        $allocatedTotal = 0;
        $lastIndex = count($percentages) - 1;

        foreach ($percentages as $index => $percentage) {
            $installmentAmount = $index === $lastIndex
                ? $amountBaisa - $allocatedTotal
                : intdiv($amountBaisa * $percentage, 100);

            $allocated[] = $installmentAmount;
            $allocatedTotal += $installmentAmount;
        }

        return $allocated;
    }
}
