<?php

namespace App\Modules\Finance\Services\Payments;

use App\Enums\PaymentProvider;
use App\Enums\PaymentRefundState;
use App\Enums\PaymentState;
use App\Modules\Finance\Contracts\PaymentGateway;
use App\Modules\Finance\Contracts\PaymentService;
use App\Modules\Finance\Data\Payments\PaymentCheckoutData;
use App\Modules\Finance\Data\Payments\PaymentCheckoutRequest;
use App\Modules\Finance\Data\Payments\PaymentRefundData;
use App\Modules\Finance\Data\Payments\PaymentVerificationData;
use App\Modules\Finance\Events\PaymentSucceeded;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\PaymentRefund;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ThawaniPaymentService implements PaymentService
{
    public function __construct(private PaymentGatewayManager $paymentGateways) {}

    public function initiate(PaymentCheckoutRequest $request): PaymentCheckoutData
    {
        if ($request->amountBaisa < 1 || $request->currency !== 'OMR' || $request->products === []) {
            throw ValidationException::withMessages(['payment' => 'The payment details are invalid.']);
        }

        $gatewayFailure = null;
        $checkout = DB::transaction(function () use ($request, &$gatewayFailure): PaymentCheckoutData {
            $existing = Payment::query()
                ->where('subject_type', $request->subjectType)
                ->where('subject_reference', $request->subjectReference)
                ->where('provider', PaymentProvider::Thawani->value)
                ->where('state', PaymentState::Pending->value)
                ->whereNotNull('provider_session_id')
                ->whereNotNull('checkout_url')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($existing instanceof Payment) {
                return $this->checkoutData($existing, $request->expiresAt);
            }

            $payment = Payment::query()->create([
                'subject_type' => $request->subjectType,
                'subject_reference' => $request->subjectReference,
                'provider' => PaymentProvider::Thawani,
                'amount_baisa' => $request->amountBaisa,
                'currency' => $request->currency,
            ]);

            $payload = [
                'client_reference_id' => $payment->reference,
                'mode' => 'payment',
                'products' => $request->products,
                'success_url' => $request->successUrl,
                'cancel_url' => $request->cancelUrl,
                'metadata' => array_merge($request->metadata, [
                    'payment_reference' => $payment->reference,
                    'subject_type' => $request->subjectType,
                    'subject_reference' => $request->subjectReference,
                ]),
            ];

            $payment->forceFill(['request_payload' => $payload])->save();

            try {
                $response = $this->gateway()->createSession($payload);
            } catch (Throwable $exception) {
                $payment->forceFill([
                    'state' => PaymentState::Failed,
                    'response_payload' => ['error' => $exception->getMessage()],
                ])->save();

                $gatewayFailure = $exception;

                return $this->checkoutData($payment, $request->expiresAt);
            }

            $sessionId = data_get($response, 'data.session_id');

            if (! is_scalar($sessionId) || trim((string) $sessionId) === '') {
                $gatewayFailure = new RuntimeException('Thawani checkout session response is invalid.');
                $payment->forceFill([
                    'state' => PaymentState::Failed,
                    'response_payload' => $response,
                ])->save();

                return $this->checkoutData($payment, $request->expiresAt);
            }

            $payment->forceFill([
                'provider_session_id' => (string) $sessionId,
                'provider_invoice' => $this->scalarString(data_get($response, 'data.invoice')),
                'provider_payment_status' => $this->scalarString(data_get($response, 'data.payment_status')),
                'checkout_url' => (string) (data_get($response, 'data.redirect_url') ?? $this->gateway()->checkoutUrl((string) $sessionId)),
                'response_payload' => $response,
            ])->save();

            return $this->checkoutData($payment->refresh(), $request->expiresAt);
        });

        if ($gatewayFailure instanceof Throwable) {
            throw $gatewayFailure;
        }

        return $checkout;
    }

    public function verifyPayment(string $paymentReference): ?PaymentVerificationData
    {
        $payment = Payment::query()->where('reference', $paymentReference)->first();

        if (! $payment instanceof Payment || $payment->subject_type === null || $payment->subject_reference === null) {
            return null;
        }

        if (in_array($payment->state, [PaymentState::Paid, PaymentState::Failed, PaymentState::Cancelled], true)) {
            return $this->verificationData($payment, $payment->state->value);
        }

        if (! $payment->provider_session_id) {
            throw new RuntimeException('Payment does not have a Thawani session.');
        }

        $response = $this->gateway()->retrieveSession($payment->provider_session_id);
        $session = data_get($response, 'data');

        if (! is_array($session)) {
            throw new RuntimeException('Thawani session payload is invalid.');
        }

        $status = strtolower((string) data_get($session, 'payment_status', ''));
        $providerAmount = data_get($session, 'total_amount');
        $providerReference = data_get($session, 'client_reference_id');
        $providerSessionId = data_get($session, 'session_id');
        $providerCurrency = data_get($session, 'currency') ?? data_get($session, 'currency_code');
        $providerPaymentId = $this->scalarString(data_get($session, 'payment_id') ?? data_get($session, 'id'));
        $providerInvoice = $this->scalarString(data_get($session, 'invoice'));

        if ($status === 'paid' && ! $this->matchesPaidSession($payment, $providerAmount, $providerReference, $providerSessionId, $providerCurrency)) {
            $payment->forceFill([
                'state' => PaymentState::Failed,
                'provider_payment_status' => $status,
                'response_payload' => $response,
                'verified_at' => now(),
            ])->save();

            throw ValidationException::withMessages(['payment' => 'Paid Thawani session does not match the local payment.']);
        }

        $payment = DB::transaction(function () use ($payment, $response, $status, $providerPaymentId, $providerInvoice): Payment {
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->state === PaymentState::Paid) {
                return $payment;
            }

            $state = match (true) {
                $status === 'paid' => PaymentState::Paid,
                in_array($status, ['cancelled', 'canceled'], true) => PaymentState::Cancelled,
                in_array($status, ['failed', 'declined', 'expired'], true) => PaymentState::Failed,
                default => PaymentState::Pending,
            };

            $payment->forceFill([
                'state' => $state,
                'provider_payment_id' => $providerPaymentId ?? $payment->provider_payment_id,
                'provider_invoice' => $providerInvoice ?? $payment->provider_invoice,
                'provider_payment_status' => $status !== '' ? $status : null,
                'response_payload' => $response,
                'verified_at' => now(),
                'paid_at' => $state === PaymentState::Paid ? ($payment->paid_at ?? now()) : $payment->paid_at,
            ])->save();

            if ($state === PaymentState::Paid) {
                event(PaymentSucceeded::fromVerification(new PaymentVerificationData(
                    $payment->subject_type,
                    $payment->subject_reference,
                    $payment->reference,
                    $payment->amount_baisa,
                    $payment->currency,
                    PaymentState::Paid->value,
                    $payment->provider_session_id,
                    $payment->provider_payment_id,
                    $payment->provider_invoice,
                )));
            }

            return $payment->refresh();
        });

        return $this->verificationData($payment, $payment->state->value);
    }

    public function verifySubject(string $subjectType, string $subjectReference): ?PaymentVerificationData
    {
        $payment = Payment::query()
            ->where('subject_type', $subjectType)
            ->where('subject_reference', $subjectReference)
            ->where('provider', PaymentProvider::Thawani->value)
            ->latest('id')
            ->first();

        return $payment instanceof Payment ? $this->verifyPayment($payment->reference) : null;
    }

    public function cancelPayment(string $paymentReference): ?PaymentVerificationData
    {
        $payment = Payment::query()->where('reference', $paymentReference)->first();

        if (! $payment instanceof Payment || $payment->subject_type === null || $payment->subject_reference === null) {
            return null;
        }

        $current = $this->verifyPayment($paymentReference);

        if (! $current || $current->status !== PaymentState::Pending->value) {
            return $current;
        }

        $payment = Payment::query()->where('reference', $paymentReference)->firstOrFail();
        if ($payment->provider_session_id) {
            $this->gateway()->cancelSession($payment->provider_session_id);
        }

        $payment = DB::transaction(function () use ($paymentReference): Payment {
            $payment = Payment::query()->where('reference', $paymentReference)->lockForUpdate()->firstOrFail();
            if ($payment->state === PaymentState::Pending) {
                $payment->forceFill([
                    'state' => PaymentState::Cancelled,
                    'provider_payment_status' => 'cancelled',
                    'verified_at' => now(),
                ])->save();
            }

            return $payment->refresh();
        });

        return $this->verificationData($payment, $payment->state->value);
    }

    public function refundPayment(string $paymentReference, int $amountBaisa, string $reason): PaymentRefundData
    {
        $paymentRefund = DB::transaction(function () use ($paymentReference, $amountBaisa, $reason): PaymentRefund {
            $payment = Payment::query()->where('reference', $paymentReference)->with('refunds')->lockForUpdate()->firstOrFail();
            if (! in_array($payment->state, [PaymentState::Paid, PaymentState::PartiallyRefunded], true)) {
                throw ValidationException::withMessages(['refund' => 'The payment is not refundable.']);
            }

            $existing = $payment->refunds
                ->first(fn (PaymentRefund $refund): bool => $refund->amount_baisa === $amountBaisa
                    && in_array($refund->state, [PaymentRefundState::Pending, PaymentRefundState::Succeeded], true));
            if ($existing instanceof PaymentRefund) {
                return $existing->load('payment');
            }

            $alreadyRefunded = (int) $payment->refunds->where('state', PaymentRefundState::Succeeded)->sum('amount_baisa');
            if ($amountBaisa < 1 || $alreadyRefunded + $amountBaisa > $payment->amount_baisa) {
                throw ValidationException::withMessages(['refund' => 'The refund amount is invalid.']);
            }

            return $payment->refunds()->create([
                'amount_baisa' => $amountBaisa,
                'currency' => $payment->currency,
                'reason' => $reason,
                'request_payload' => [
                    'amount' => $amountBaisa,
                    'reason' => $reason,
                ],
            ])->load('payment');
        });

        if ($paymentRefund->state === PaymentRefundState::Succeeded) {
            return $this->refundData($paymentRefund);
        }

        $payment = $paymentRefund->payment;
        $providerPaymentId = $payment->provider_payment_id;
        if (! $providerPaymentId && $payment->provider_invoice) {
            $providerPaymentId = $this->scalarString(data_get($this->gateway()->listPaymentsByInvoice($payment->provider_invoice), 'data.0.payment_id'));
        }
        if (! $providerPaymentId) {
            throw new RuntimeException('Unable to resolve the Thawani payment id.');
        }

        $payload = ['payment_id' => $providerPaymentId, 'reason' => $reason, 'amount' => $amountBaisa];
        $paymentRefund->forceFill([
            'provider_payment_id' => $providerPaymentId,
            'request_payload' => $payload,
        ])->save();
        try {
            $response = $this->gateway()->createRefund($payload);
        } catch (Throwable $exception) {
            $paymentRefund->forceFill(['state' => PaymentRefundState::Failed, 'response_payload' => ['error' => $exception->getMessage()], 'processed_at' => now()])->save();
            throw $exception;
        }

        return DB::transaction(function () use ($paymentRefund, $payment, $providerPaymentId, $response): PaymentRefundData {
            $refund = PaymentRefund::query()->whereKey($paymentRefund->id)->lockForUpdate()->firstOrFail();
            $refund->forceFill([
                'state' => PaymentRefundState::Succeeded,
                'provider_refund_id' => $this->scalarString(data_get($response, 'data.refund_id')),
                'provider_payment_id' => $providerPaymentId,
                'provider_status' => $this->scalarString(data_get($response, 'data.status')) ?? 'succeeded',
                'response_payload' => $response,
                'processed_at' => now(),
            ])->save();

            $payment = Payment::query()->whereKey($payment->id)->with('refunds')->lockForUpdate()->firstOrFail();
            $refunded = (int) $payment->refunds->where('state', PaymentRefundState::Succeeded)->sum('amount_baisa');
            $payment->forceFill(['provider_payment_id' => $providerPaymentId, 'state' => $refunded >= $payment->amount_baisa ? PaymentState::Refunded : PaymentState::PartiallyRefunded])->save();

            return $this->refundData($refund->load('payment'));
        });
    }

    private function gateway(): PaymentGateway
    {
        $gateway = $this->paymentGateways->driver(PaymentProvider::Thawani->value);
        if (! $gateway instanceof PaymentGateway) {
            throw new RuntimeException('Thawani payment gateway is not configured.');
        }

        return $gateway;
    }

    private function matchesPaidSession(Payment $payment, mixed $amount, mixed $reference, mixed $sessionId, mixed $currency): bool
    {
        $matches = is_numeric($amount) && (int) $amount === $payment->amount_baisa;

        if ($payment->subject_type === null) {
            return $matches
                && (! is_scalar($reference) || trim((string) $reference) === '' || (string) $reference === $payment->reference)
                && (! is_scalar($sessionId) || trim((string) $sessionId) === '' || (string) $sessionId === $payment->provider_session_id);
        }

        return $matches
            && is_scalar($reference)
            && trim((string) $reference) !== ''
            && (string) $reference === $payment->reference
            && is_scalar($sessionId)
            && trim((string) $sessionId) !== ''
            && (string) $sessionId === $payment->provider_session_id
            && is_scalar($currency)
            && trim((string) $currency) !== ''
            && strtoupper((string) $currency) === $payment->currency;
    }

    private function scalarString(mixed $value): ?string
    {
        return is_scalar($value) && trim((string) $value) !== '' ? (string) $value : null;
    }

    private function checkoutData(Payment $payment, ?DateTimeInterface $expiresAt): PaymentCheckoutData
    {
        return new PaymentCheckoutData($payment->reference, $payment->state->value, (string) $payment->checkout_url, $payment->amount_baisa, $payment->currency, $payment->provider_session_id, $expiresAt, $payment->provider_invoice);
    }

    private function verificationData(Payment $payment, string $status): PaymentVerificationData
    {
        return new PaymentVerificationData($payment->subject_type ?? '', $payment->subject_reference ?? '', $payment->reference, $payment->amount_baisa, $payment->currency, $status, $payment->provider_session_id, $payment->provider_payment_id, $payment->provider_invoice);
    }

    private function refundData(PaymentRefund $refund): PaymentRefundData
    {
        return new PaymentRefundData($refund->payment->reference, $refund->reference, $refund->state->value, $refund->amount_baisa, $refund->currency);
    }
}
