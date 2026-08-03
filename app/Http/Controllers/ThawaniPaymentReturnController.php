<?php

namespace App\Http\Controllers;

use App\Enums\PaymentState;
use App\Modules\Finance\Actions\ConfirmThawaniPayment;
use App\Modules\Finance\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Throwable;

class ThawaniPaymentReturnController extends Controller
{
    public function success(Payment $payment, ConfirmThawaniPayment $confirmThawaniPayment): RedirectResponse
    {
        try {
            $payment = $confirmThawaniPayment->confirm($payment);

            session()->flash('status', $payment->state === PaymentState::Paid
                ? __('ui.messages.payment_completed')
                : __('ui.messages.payment_not_confirmed'));
        } catch (Throwable) {
            session()->flash('status', __('ui.messages.payment_not_confirmed'));
        }

        return $this->redirectToBooking($payment);
    }

    public function cancel(Payment $payment, ConfirmThawaniPayment $confirmThawaniPayment): RedirectResponse
    {
        $payment = $confirmThawaniPayment->cancel($payment);

        session()->flash('status', __('ui.messages.payment_cancelled'));

        return $this->redirectToBooking($payment);
    }

    private function redirectToBooking(Payment $payment): RedirectResponse
    {
        $payment->loadMissing('bookingInstallment.paymentSchedule.booking');

        return redirect()->route('customer.bookings.show', $payment->bookingInstallment->paymentSchedule->booking);
    }
}
