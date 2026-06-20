<?php

namespace App\Actions;

use App\Contracts\Payments\PaymentGateway;
use App\Enums\BookingInstallmentState;
use App\Enums\PaymentRefundState;
use App\Enums\PaymentState;
use App\Exceptions\PaymentGatewayException;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class RefundPayment
{
    public function __construct(
        private PaymentGatewayManager $paymentGateways,
        private PostRefundLedgerTransaction $postRefundLedgerTransaction,
    ) {}

    public function execute(Payment $payment, ?int $amountBaisa = null, string $reason = 'Customer refund'): PaymentRefund
    {
        $paymentRefund = DB::transaction(function () use ($payment, $amountBaisa, $reason): PaymentRefund {
            $payment = Payment::query()
                ->whereKey($payment->id)
                ->with(['refunds', 'bookingInstallment'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($payment->state, [PaymentState::Paid, PaymentState::PartiallyRefunded], true)) {
                throw ValidationException::withMessages([
                    'refund' => __('ui.messages.payment_not_refundable'),
                ]);
            }

            $alreadyRefundedBaisa = $payment->refunds
                ->where('state', PaymentRefundState::Succeeded)
                ->sum('amount_baisa');
            $pendingRefundBaisa = $payment->refunds
                ->where('state', PaymentRefundState::Pending)
                ->sum('amount_baisa');
            $remainingBaisa = $payment->amount_baisa - $alreadyRefundedBaisa - $pendingRefundBaisa;
            $refundAmountBaisa = $amountBaisa ?? $remainingBaisa;

            if ($refundAmountBaisa <= 0 || $refundAmountBaisa > $remainingBaisa) {
                throw ValidationException::withMessages([
                    'refund' => __('ui.messages.refund_amount_invalid'),
                ]);
            }

            return $payment->refunds()->create([
                'amount_baisa' => $refundAmountBaisa,
                'currency' => $payment->currency,
                'reason' => $reason,
            ]);
        });

        $paymentRefund->loadMissing('payment');
        $providerPaymentId = $this->providerPaymentId($paymentRefund->payment);
        $payload = [
            'payment_id' => $providerPaymentId,
            'reason' => $paymentRefund->reason,
            'amount' => $paymentRefund->amount_baisa,
        ];

        $paymentRefund->forceFill([
            'provider_payment_id' => $providerPaymentId,
            'request_payload' => $payload,
        ])->save();

        try {
            $response = $this->gateway($paymentRefund->payment)->createRefund($payload);
        } catch (Throwable $exception) {
            $paymentRefund->forceFill([
                'state' => PaymentRefundState::Failed,
                'response_payload' => $this->exceptionPayload($exception),
                'processed_at' => now(),
            ])->save();

            throw ValidationException::withMessages([
                'refund' => __('ui.messages.refund_gateway_unavailable'),
            ]);
        }

        $refundId = (string) data_get($response, 'data.refund_id');
        $providerStatus = (string) data_get($response, 'data.status', 'succeeded');

        return DB::transaction(function () use ($paymentRefund, $response, $refundId, $providerPaymentId, $providerStatus): PaymentRefund {
            $paymentRefund = PaymentRefund::query()
                ->whereKey($paymentRefund->id)
                ->with('payment.bookingInstallment')
                ->lockForUpdate()
                ->firstOrFail();

            $paymentRefund->forceFill([
                'state' => PaymentRefundState::Succeeded,
                'provider_refund_id' => $refundId,
                'provider_payment_id' => $providerPaymentId,
                'provider_status' => $providerStatus,
                'response_payload' => $response,
                'processed_at' => now(),
            ])->save();

            $payment = $paymentRefund->payment;
            $payment->forceFill([
                'provider_payment_id' => $providerPaymentId,
                'state' => $this->paymentStateAfterRefund($payment),
            ])->save();

            if ($payment->state === PaymentState::Refunded) {
                $payment->bookingInstallment->forceFill([
                    'state' => BookingInstallmentState::Pending,
                    'paid_at' => null,
                ])->save();
            }

            $this->postRefundLedgerTransaction->execute($paymentRefund);

            return $paymentRefund->refresh();
        });
    }

    private function providerPaymentId(Payment $payment): string
    {
        if ($payment->provider_payment_id) {
            return $payment->provider_payment_id;
        }

        if (! $payment->provider_invoice) {
            throw new RuntimeException('Payment does not have a Thawani invoice.');
        }

        $response = $this->gateway($payment)->listPaymentsByInvoice($payment->provider_invoice);
        $providerPaymentId = data_get($response, 'data.0.payment_id')
            ?? data_get($response, 'data.0.id');

        if (! is_scalar($providerPaymentId) || (string) $providerPaymentId === '') {
            throw new RuntimeException('Unable to resolve Thawani payment id.');
        }

        return (string) $providerPaymentId;
    }

    private function paymentStateAfterRefund(Payment $payment): PaymentState
    {
        $payment->load('refunds');

        $succeededRefundTotal = $payment->refunds
            ->where('state', PaymentRefundState::Succeeded)
            ->sum('amount_baisa');

        return $succeededRefundTotal >= $payment->amount_baisa
            ? PaymentState::Refunded
            : PaymentState::PartiallyRefunded;
    }

    private function gateway(Payment $payment): PaymentGateway
    {
        $gateway = $this->paymentGateways->driver($payment->provider);

        if (! $gateway instanceof PaymentGateway) {
            throw new RuntimeException("Payment provider [{$payment->provider}] is not supported.");
        }

        return $gateway;
    }

    /**
     * @return array<string, mixed>
     */
    private function exceptionPayload(Throwable $exception): array
    {
        if ($exception instanceof PaymentGatewayException) {
            return $exception->payload();
        }

        return [
            'error' => $exception->getMessage(),
        ];
    }
}
