<?php

namespace App\Actions;

use App\Enums\BookingInstallmentState;
use App\Enums\PaymentRefundState;
use App\Enums\PaymentState;
use App\Modules\Events\Actions\ReleaseBookingSeats;
use App\Modules\Finance\Actions\PostRefundLedgerTransaction;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\PaymentRefund;
use App\Notifications\PaymentRefundedNotification;
use App\Services\Webhooks\ByruhaaWebhookSender;
use App\Support\Money\MoneyFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompletePaymentRefund
{
    public function __construct(
        private PostRefundLedgerTransaction $postRefundLedgerTransaction,
        private ReleaseBookingSeats $releaseBookingSeats,
        private ByruhaaWebhookSender $webhookSender,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function execute(PaymentRefund $refund, array $attributes): PaymentRefund
    {
        $refund = DB::transaction(function () use ($refund, $attributes): PaymentRefund {
            $refund = PaymentRefund::query()->whereKey($refund->id)
                ->with('payment.bookingInstallment')->lockForUpdate()->firstOrFail();
            if (! in_array($refund->state, [PaymentRefundState::Pending, PaymentRefundState::ManualRequired], true)) {
                throw ValidationException::withMessages([
                    'refund' => 'This refund has already been resolved.',
                ]);
            }

            $refund->forceFill([
                ...$attributes,
                'state' => PaymentRefundState::Succeeded,
                'processed_at' => $attributes['processed_at'] ?? now(),
            ])->save();

            $payment = $refund->payment;
            $payment->forceFill([
                'provider_payment_id' => $attributes['provider_payment_id'] ?? $payment->provider_payment_id,
                'state' => $this->paymentStateAfterRefund($payment),
            ])->save();
            if ($payment->state === PaymentState::Refunded) {
                $payment->bookingInstallment->forceFill([
                    'state' => BookingInstallmentState::Pending,
                    'paid_at' => null,
                ])->save();
            }

            $this->postRefundLedgerTransaction->execute($refund);

            return $refund->refresh();
        });

        if ($refund->payment->state === PaymentState::Refunded) {
            $this->releaseBookingSeats->execute($refund->payment);
        }

        $refund->loadMissing('payment.bookingInstallment.paymentSchedule.booking.customer');
        $refund->payment->bookingInstallment->paymentSchedule->booking->customer
            ->notify(new PaymentRefundedNotification($refund->id));
        $this->webhookSender->sendPaymentRefunded($refund);

        return $refund;
    }

    private function paymentStateAfterRefund(Payment $payment): PaymentState
    {
        $payment->load('refunds');
        $succeeded = MoneyFactory::fromMinor((int) $payment->refunds
            ->where('state', PaymentRefundState::Succeeded)
            ->sum('amount_baisa'), $payment->currency);

        return $succeeded->isGreaterThanOrEqualTo($payment->amount)
            ? PaymentState::Refunded
            : PaymentState::PartiallyRefunded;
    }
}
