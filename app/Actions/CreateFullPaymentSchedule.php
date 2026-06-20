<?php

namespace App\Actions;

use App\Models\Booking;
use App\Models\BookingPaymentSchedule;
use App\States\Booking\Approved;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateFullPaymentSchedule
{
    public function execute(Booking $booking): BookingPaymentSchedule
    {
        return DB::transaction(function () use ($booking): BookingPaymentSchedule {
            $booking = Booking::query()
                ->whereKey($booking->id)
                ->with(['familyMembers.contract', 'paymentSchedule'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $booking->state instanceof Approved) {
                throw ValidationException::withMessages([
                    'payment' => __('ui.messages.booking_must_be_approved_for_payment_plan'),
                ]);
            }

            if (! $booking->hasSignedContracts()) {
                throw ValidationException::withMessages([
                    'payment' => __('ui.messages.contracts_must_be_signed_for_payment_plan'),
                ]);
            }

            if ($booking->paymentSchedule) {
                throw ValidationException::withMessages([
                    'payment' => __('ui.messages.payment_plan_already_selected'),
                ]);
            }

            $schedule = $booking->paymentSchedule()->create([
                'event_payment_plan_id' => null,
                'plan_name' => __('ui.payments.full_payment'),
                'currency' => $booking->currency,
                'subtotal_baisa' => $booking->subtotal_baisa,
                'discount_amount_baisa' => $booking->discount_amount_baisa,
                'total_baisa' => $booking->total_baisa,
            ]);

            $schedule->installments()->create([
                'name' => __('ui.payments.full_payment'),
                'sequence' => 1,
                'percentage' => 100,
                'due_date' => now()->toDateString(),
                'gross_amount_baisa' => $booking->subtotal_baisa,
                'discount_amount_baisa' => $booking->discount_amount_baisa,
                'amount_baisa' => $booking->total_baisa,
            ]);

            return $schedule->load('installments');
        });
    }
}
