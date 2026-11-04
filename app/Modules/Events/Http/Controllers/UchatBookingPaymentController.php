<?php

namespace App\Modules\Events\Http\Controllers;

use App\Enums\BookingInstallmentState;
use App\Http\Controllers\Controller;
use App\Modules\Events\Actions\CreateFullPaymentSchedule;
use App\Modules\Events\Actions\SelectBookingPaymentPlan;
use App\Modules\Events\Http\Requests\InitiateCustomerBookingPaymentRequest;
use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\BookingInstallment;
use App\Modules\Events\Models\BookingPaymentSchedule;
use App\Modules\Events\Models\EventPaymentPlan;
use App\Modules\Finance\Actions\InitiateInstallmentPayment;
use App\Modules\Finance\Http\Resources\UchatPaymentResource as PaymentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class UchatBookingPaymentController extends Controller
{
    public function store(
        InitiateCustomerBookingPaymentRequest $request,
        string $reference,
        CreateFullPaymentSchedule $createFullPaymentSchedule,
        SelectBookingPaymentPlan $selectBookingPaymentPlan,
        InitiateInstallmentPayment $initiateInstallmentPayment,
    ): JsonResponse {
        $customer = $request->customer();
        $booking = $customer->bookings()->where('reference', $reference)->firstOrFail();

        $validated = $request->validated();
        $schedule = $this->paymentSchedule($booking, $validated, $createFullPaymentSchedule, $selectBookingPaymentPlan);
        $installment = $this->installment($schedule, isset($validated['booking_installment_id']) ? (int) $validated['booking_installment_id'] : null);
        $payment = $initiateInstallmentPayment->execute($installment, $customer->id);

        return PaymentResource::make($payment->load('bookingInstallment'))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * @param  array{payment_plan_id?: int|null, booking_installment_id?: int|null}  $validated
     */
    private function paymentSchedule(
        Booking $booking,
        array $validated,
        CreateFullPaymentSchedule $createFullPaymentSchedule,
        SelectBookingPaymentPlan $selectBookingPaymentPlan,
    ): BookingPaymentSchedule {
        $booking->loadMissing('paymentSchedule.installments');

        if ($booking->paymentSchedule instanceof BookingPaymentSchedule) {
            if (($validated['payment_plan_id'] ?? null) !== null) {
                throw ValidationException::withMessages([
                    'payment_plan_id' => __('ui.messages.payment_plan_already_selected'),
                ]);
            }

            return $booking->paymentSchedule;
        }

        if (($validated['payment_plan_id'] ?? null) !== null) {
            $paymentPlan = EventPaymentPlan::query()->whereKey((int) $validated['payment_plan_id'])->firstOrFail();

            return $selectBookingPaymentPlan->execute($booking, $paymentPlan);
        }

        return $createFullPaymentSchedule->execute($booking);
    }

    private function installment(BookingPaymentSchedule $schedule, ?int $installmentId): BookingInstallment
    {
        if ($installmentId !== null) {
            return $schedule->installments()
                ->whereKey($installmentId)
                ->firstOrFail();
        }

        return $schedule->installments()
            ->where('state', BookingInstallmentState::Pending->value)
            ->orderBy('sequence')
            ->firstOrFail();
    }
}
