<?php

namespace App\Actions;

use App\Contracts\Payments\PaymentGateway;
use App\Enums\BookingInstallmentState;
use App\Enums\PaymentState;
use App\Models\Booking;
use App\Models\BookingSeatAllocation;
use App\Models\Event;
use App\Models\Payment;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Webhooks\ByruhaaWebhookSender;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ConfirmThawaniPayment
{
    public function __construct(
        private PaymentGatewayManager $paymentGateways,
        private PostPaymentLedgerTransaction $postPaymentLedgerTransaction,
        private PostAffiliateCommissionForPayment $postAffiliateCommissionForPayment,
        private ByruhaaWebhookSender $webhookSender,
        private ReserveBookingSeats $reserveBookingSeats,
        private ReleaseBookingSeats $releaseBookingSeats,
    ) {}

    public function confirm(Payment $payment): Payment
    {
        if ($payment->state === PaymentState::Paid) {
            $this->reserveBookingSeats->execute($payment);
            $this->postPaymentLedgerTransaction->execute($payment);
            $this->postAffiliateCommissionForPayment->execute($payment);

            return $payment->refresh();
        }

        if (! $payment->provider_session_id) {
            throw new RuntimeException('Payment does not have a Thawani session.');
        }

        $response = $this->gateway($payment)->retrieveSession($payment->provider_session_id);
        $session = data_get($response, 'data');

        if (! is_array($session)) {
            throw new RuntimeException('Thawani session payload is invalid.');
        }

        $providerPaymentStatus = strtolower((string) data_get($session, 'payment_status', ''));
        $providerPaymentId = data_get($session, 'payment_id') ?? data_get($session, 'id');
        $providerInvoice = data_get($session, 'invoice');
        $providerAmount = data_get($session, 'total_amount');
        $providerReference = data_get($session, 'client_reference_id');
        $providerSessionId = data_get($session, 'session_id');
        $becamePaid = false;
        $paidVerificationError = null;
        [$bookingId, $eventId] = $this->bookingAndEventIds($payment);

        $payment = DB::transaction(function () use ($payment, $bookingId, $eventId, $response, $providerPaymentStatus, $providerPaymentId, $providerInvoice, $providerAmount, $providerReference, $providerSessionId, &$becamePaid, &$paidVerificationError): Payment {
            Event::query()->whereKey($eventId)->lockForUpdate()->firstOrFail();
            Booking::query()->whereKey($bookingId)->lockForUpdate()->firstOrFail();
            BookingSeatAllocation::query()->where('booking_id', $bookingId)->lockForUpdate()->first();
            $payment = Payment::query()->whereKey($payment->id)->with('bookingInstallment')->lockForUpdate()->firstOrFail();

            if ($payment->state === PaymentState::Paid) {
                return $payment;
            }

            $state = match (true) {
                $providerPaymentStatus === 'paid' => PaymentState::Paid,
                in_array($providerPaymentStatus, ['cancelled', 'canceled'], true) => PaymentState::Cancelled,
                in_array($providerPaymentStatus, ['failed', 'declined', 'expired'], true) => PaymentState::Failed,
                default => PaymentState::Pending,
            };

            if ($state === PaymentState::Paid) {
                $paidVerificationError = $this->paidVerificationError($payment, $providerAmount, $providerReference, $providerSessionId);
            }

            if ($paidVerificationError !== null) {
                $state = PaymentState::Failed;
            }

            $payment->forceFill([
                'state' => $state,
                'provider_payment_id' => is_scalar($providerPaymentId) ? (string) $providerPaymentId : $payment->provider_payment_id,
                'provider_invoice' => is_scalar($providerInvoice) ? (string) $providerInvoice : $payment->provider_invoice,
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
                $this->postAffiliateCommissionForPayment->execute($payment);
                $becamePaid = true;
            }

            return $payment->refresh();
        });

        if ($payment->state === PaymentState::Paid) {
            $this->reserveBookingSeats->execute($payment);
        } elseif ($paidVerificationError === null && in_array($payment->state, [PaymentState::Cancelled, PaymentState::Failed], true)) {
            $this->releaseBookingSeats->execute($payment, true);
        }

        if ($becamePaid) {
            $this->webhookSender->sendPaymentPaid($payment);
        }

        if ($paidVerificationError !== null) {
            throw new RuntimeException($paidVerificationError);
        }

        return $payment;
    }

    public function cancel(Payment $payment): Payment
    {
        if ($payment->state === PaymentState::Paid) {
            $this->reserveBookingSeats->execute($payment);

            return $payment->refresh();
        }

        if ($payment->provider_session_id) {
            $payment = $this->confirm($payment);

            if ($payment->state === PaymentState::Paid) {
                return $payment;
            }

            if ($payment->state === PaymentState::Pending) {
                $this->gateway($payment)->cancelSession($payment->provider_session_id);
            }
        }

        [$bookingId, $eventId] = $this->bookingAndEventIds($payment);

        $payment = DB::transaction(function () use ($payment, $bookingId, $eventId): Payment {
            Event::query()->whereKey($eventId)->lockForUpdate()->firstOrFail();
            Booking::query()->whereKey($bookingId)->lockForUpdate()->firstOrFail();
            $payment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($payment->state !== PaymentState::Paid) {
                $payment->forceFill([
                    'state' => PaymentState::Cancelled,
                    'provider_payment_status' => 'cancelled',
                    'verified_at' => now(),
                ])->save();
            }

            return $payment->refresh();
        });

        $this->releaseBookingSeats->execute($payment, true);

        return $payment;
    }

    private function gateway(Payment $payment): PaymentGateway
    {
        $gateway = $this->paymentGateways->driver($payment->provider->value);

        if (! $gateway instanceof PaymentGateway) {
            throw new RuntimeException("Payment provider [{$payment->provider->value}] is not supported.");
        }

        return $gateway;
    }

    /** @return array{int, int} */
    private function bookingAndEventIds(Payment $payment): array
    {
        $payment->loadMissing('bookingInstallment.paymentSchedule.booking');
        $booking = $payment->bookingInstallment->paymentSchedule->booking;

        return [$booking->id, $booking->event_id];
    }

    private function paidVerificationError(Payment $payment, mixed $providerAmount, mixed $providerReference, mixed $providerSessionId): ?string
    {
        if (! is_numeric($providerAmount)) {
            return 'Paid Thawani session is missing a valid total amount.';
        }

        if ((int) $providerAmount !== $payment->amount_baisa) {
            return 'Paid Thawani session amount does not match the local payment.';
        }

        if (is_scalar($providerReference) && trim((string) $providerReference) !== '' && (string) $providerReference !== $payment->reference) {
            return 'Paid Thawani session reference does not match the local payment.';
        }

        if (is_scalar($providerSessionId) && trim((string) $providerSessionId) !== '' && (string) $providerSessionId !== $payment->provider_session_id) {
            return 'Paid Thawani session ID does not match the local payment.';
        }

        return null;
    }
}
