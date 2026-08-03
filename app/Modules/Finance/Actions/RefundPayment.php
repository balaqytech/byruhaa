<?php

namespace App\Modules\Finance\Actions;

use App\Actions\CompletePaymentRefund;
use App\Enums\PaymentRefundState;
use App\Enums\PaymentState;
use App\Exceptions\PaymentGatewayException;
use App\Modules\Finance\Contracts\PaymentGateway;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\PaymentRefund;
use App\Modules\Finance\Services\Payments\PaymentGatewayManager;
use App\Support\Money\MoneyFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class RefundPayment
{
    public function __construct(
        private PaymentGatewayManager $paymentGateways,
        private CompletePaymentRefund $completePaymentRefund,
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

            $alreadyRefunded = MoneyFactory::fromMinor((int) $payment->refunds
                ->where('state', PaymentRefundState::Succeeded)
                ->sum('amount_baisa'), $payment->currency);
            $reservedRefund = MoneyFactory::fromMinor((int) $payment->refunds
                ->whereIn('state', [PaymentRefundState::Pending, PaymentRefundState::ManualRequired])
                ->sum('amount_baisa'), $payment->currency);
            $remaining = $payment->amount
                ->minus($alreadyRefunded)
                ->minus($reservedRefund);
            $refundAmount = $amountBaisa === null
                ? $remaining
                : MoneyFactory::fromMinor($amountBaisa, $payment->currency);

            if ($refundAmount->isZero() || $refundAmount->isNegative() || $refundAmount->isGreaterThan($remaining)) {
                throw ValidationException::withMessages([
                    'refund' => __('ui.messages.refund_amount_invalid'),
                ]);
            }

            return $payment->refunds()->create([
                'amount_baisa' => MoneyFactory::toMinor($refundAmount),
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
            $manualRequired = $this->requiresManualRefund($exception);
            $paymentRefund->forceFill([
                'state' => $manualRequired ? PaymentRefundState::ManualRequired : PaymentRefundState::Failed,
                'provider_status' => $manualRequired ? 'manual_required' : 'failed',
                'response_payload' => $this->exceptionPayload($exception),
                'manual_required_at' => $manualRequired ? now() : null,
                'processed_at' => now(),
            ])->save();

            throw ValidationException::withMessages([
                'refund' => $manualRequired
                    ? 'رفضت ثواني الاسترداد الآلي، ويلزم إتمامه يدويًا من سجل الاستردادات.'
                    : __('ui.messages.refund_gateway_unavailable'),
            ]);
        }

        $refundId = (string) data_get($response, 'data.refund_id');
        $providerStatus = (string) data_get($response, 'data.status', 'succeeded');

        return $this->completePaymentRefund->execute($paymentRefund, [
            'resolution_method' => 'automatic',
            'provider_refund_id' => $refundId,
            'provider_payment_id' => $providerPaymentId,
            'provider_status' => $providerStatus,
            'response_payload' => $response,
        ]);
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

    private function gateway(Payment $payment): PaymentGateway
    {
        $gateway = $this->paymentGateways->driver($payment->provider->value);

        if (! $gateway instanceof PaymentGateway) {
            throw new RuntimeException("Payment provider [{$payment->provider->value}] is not supported.");
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

    private function requiresManualRefund(Throwable $exception): bool
    {
        if (! $exception instanceof PaymentGatewayException) {
            return false;
        }

        $payload = $exception->payload();

        return (int) data_get($payload, 'response.code') === 4300
            || (int) data_get($payload, 'code') === 4300;
    }
}
