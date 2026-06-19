<?php

namespace App\Actions;

use App\Enums\BookingInstallmentState;
use App\Enums\PaymentState;
use App\Models\Payment;
use App\Services\Thawani\ThawaniClient;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ConfirmThawaniPayment
{
    public function __construct(
        private ThawaniClient $thawaniClient,
        private PostPaymentLedgerTransaction $postPaymentLedgerTransaction,
    ) {}

    public function confirm(Payment $payment): Payment
    {
        if ($payment->state === PaymentState::Paid) {
            $this->postPaymentLedgerTransaction->execute($payment);

            return $payment;
        }

        if (! $payment->provider_session_id) {
            throw new RuntimeException('Payment does not have a Thawani session.');
        }

        $response = $this->thawaniClient->retrieveSession($payment->provider_session_id);
        $session = data_get($response, 'data');

        if (! is_array($session)) {
            throw new RuntimeException('Thawani session payload is invalid.');
        }

        $providerPaymentStatus = strtolower((string) data_get($session, 'payment_status', ''));
        $providerPaymentId = data_get($session, 'payment_id') ?? data_get($session, 'id');
        $providerInvoice = data_get($session, 'invoice');
        $providerAmount = data_get($session, 'total_amount');

        return DB::transaction(function () use ($payment, $response, $providerPaymentStatus, $providerPaymentId, $providerInvoice, $providerAmount): Payment {
            $payment = Payment::query()
                ->whereKey($payment->id)
                ->with('bookingInstallment')
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->state === PaymentState::Paid) {
                return $payment;
            }

            $state = match (true) {
                $providerPaymentStatus === 'paid' => PaymentState::Paid,
                in_array($providerPaymentStatus, ['cancelled', 'canceled'], true) => PaymentState::Cancelled,
                in_array($providerPaymentStatus, ['failed', 'declined', 'expired'], true) => PaymentState::Failed,
                default => PaymentState::Pending,
            };

            if ($state === PaymentState::Paid && is_numeric($providerAmount) && (int) $providerAmount !== $payment->amount_baisa) {
                $state = PaymentState::Failed;
            }

            $payment->forceFill([
                'state' => $state,
                'provider_payment_id' => is_scalar($providerPaymentId) ? (string) $providerPaymentId : $payment->provider_payment_id,
                'provider_invoice' => is_scalar($providerInvoice) ? (string) $providerInvoice : null,
                'provider_payment_status' => $providerPaymentStatus ?: null,
                'response_payload' => $response,
                'verified_at' => now(),
                'paid_at' => $state === PaymentState::Paid ? now() : $payment->paid_at,
            ])->save();

            if ($state === PaymentState::Paid) {
                $payment->bookingInstallment->forceFill([
                    'state' => BookingInstallmentState::Paid,
                    'paid_at' => $payment->paid_at,
                ])->save();

                $this->postPaymentLedgerTransaction->execute($payment);
            }

            return $payment->refresh();
        });
    }

    public function cancel(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment): Payment {
            $payment = Payment::query()
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->state !== PaymentState::Paid) {
                $payment->forceFill([
                    'state' => PaymentState::Cancelled,
                    'provider_payment_status' => 'cancelled',
                    'verified_at' => now(),
                ])->save();
            }

            return $payment->refresh();
        });
    }
}
