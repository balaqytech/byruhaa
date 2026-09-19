<?php

namespace App\Modules\Finance\Actions;

use App\Actions\CompletePaymentRefund;
use App\Enums\PaymentRefundState;
use App\Enums\PaymentState;
use App\Exceptions\PaymentGatewayException;
use App\Modules\Finance\Contracts\PaymentGateway;
use App\Modules\Finance\Enums\WalletTopUpStatus;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\PaymentRefund;
use App\Modules\Finance\Models\WalletTopUp;
use App\Modules\Finance\Services\Payments\PaymentGatewayManager;
use App\Support\Money\MoneyFactory;
use Illuminate\Http\Client\ConnectionException;
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

            $walletTopUp = $this->walletTopUpForPayment($payment);
            if ($walletTopUp instanceof WalletTopUp) {
                if ($walletTopUp->refund_deadline_at === null || ! $walletTopUp->refund_deadline_at->isFuture()) {
                    throw ValidationException::withMessages([
                        'refund' => 'The wallet top-up refund window has expired.',
                    ]);
                }

                $availableRefund = $walletTopUp->refundable_baisa - $walletTopUp->reserved_refund_baisa;

                if (! in_array($walletTopUp->status, [WalletTopUpStatus::Credited->value, WalletTopUpStatus::Refunding->value], true)
                    || MoneyFactory::toMinor($refundAmount) > $availableRefund) {
                    throw ValidationException::withMessages([
                        'refund' => 'The wallet top-up does not have enough refundable funds.',
                    ]);
                }
            }

            $refund = $payment->refunds()->create([
                'amount_baisa' => MoneyFactory::toMinor($refundAmount),
                'currency' => $payment->currency,
                'reason' => $reason,
            ]);

            if ($walletTopUp instanceof WalletTopUp) {
                $walletTopUp->forceFill(['status' => WalletTopUpStatus::Refunding])->save();
                $walletTopUp->increment('reserved_refund_baisa', $refund->amount_baisa);
            }

            return $refund;
        });

        $paymentRefund->loadMissing('payment');

        try {
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

            if (! $manualRequired) {
                $this->releaseWalletTopUpReservation($paymentRefund);
            }

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

    private function walletTopUpForPayment(Payment $payment): ?WalletTopUp
    {
        if ($payment->subject_type !== 'wallet_topup' || $payment->subject_reference === null) {
            return null;
        }

        $walletTopUp = WalletTopUp::query()
            ->where('reference', $payment->subject_reference)
            ->lockForUpdate()
            ->first();

        if (! $walletTopUp instanceof WalletTopUp) {
            throw ValidationException::withMessages(['refund' => 'The wallet top-up could not be found.']);
        }

        return $walletTopUp;
    }

    private function releaseWalletTopUpReservation(PaymentRefund $refund): void
    {
        DB::transaction(function () use ($refund): void {
            $refund->loadMissing('payment');
            $payment = $refund->payment;

            if ($payment->subject_type !== 'wallet_topup' || $payment->subject_reference === null) {
                return;
            }

            $walletTopUp = WalletTopUp::query()
                ->where('reference', $payment->subject_reference)
                ->lockForUpdate()
                ->first();

            if (! $walletTopUp instanceof WalletTopUp) {
                return;
            }

            $walletTopUp->forceFill([
                'reserved_refund_baisa' => max(0, $walletTopUp->reserved_refund_baisa - $refund->amount_baisa),
            ])->save();

            $hasOpenRefund = PaymentRefund::query()
                ->where('payment_id', $payment->id)
                ->whereIn('state', [PaymentRefundState::Pending->value, PaymentRefundState::ManualRequired->value])
                ->exists();

            if (! $hasOpenRefund && $walletTopUp->status === WalletTopUpStatus::Refunding->value) {
                $walletTopUp->forceFill(['status' => WalletTopUpStatus::Credited])->save();
            }
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
        if ($exception instanceof ConnectionException) {
            return true;
        }

        if (! $exception instanceof PaymentGatewayException) {
            return false;
        }

        $payload = $exception->payload();

        $status = (int) data_get($payload, 'status', data_get($payload, 'response.status', 0));

        return (int) data_get($payload, 'response.code') === 4300
            || (int) data_get($payload, 'code') === 4300
            || $status === 0
            || $status === 429
            || $status >= 500;
    }
}
