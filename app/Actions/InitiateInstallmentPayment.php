<?php

namespace App\Actions;

use App\Contracts\Payments\PaymentGateway;
use App\Enums\BookingInstallmentState;
use App\Enums\EventStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentState;
use App\Exceptions\PaymentGatewayException;
use App\Models\BookingInstallment;
use App\Models\Payment;
use App\States\Booking\Cancelled;
use App\States\Booking\Rejected;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class InitiateInstallmentPayment
{
    public function __construct(
        private PaymentGateway $paymentGateway,
        private PrepareBookingSeatHold $prepareBookingSeatHold,
        private ReserveBookingSeats $reserveBookingSeats,
        private ReleaseBookingSeats $releaseBookingSeats,
    ) {}

    public function execute(BookingInstallment $installment, int $customerId): Payment
    {
        $installment = BookingInstallment::query()
            ->whereKey($installment->id)
            ->whereHas('paymentSchedule.booking', fn ($query) => $query->where('customer_id', $customerId))
            ->with(['paymentSchedule.booking.customer', 'paymentSchedule.booking.event'])
            ->firstOrFail();

        $installment->paymentSchedule->booking->customer->ensureProfileIsComplete('payment');
        $this->validatePayable($installment);
        if ($installment->paymentSchedule->booking->familyMembers()->exists()) {
            $this->prepareBookingSeatHold->execute($installment->paymentSchedule->booking_id);
        }

        $payment = DB::transaction(function () use ($installment, $customerId): Payment {
            $installment = BookingInstallment::query()
                ->whereKey($installment->id)
                ->whereHas('paymentSchedule.booking', fn ($query) => $query->where('customer_id', $customerId))
                ->with(['paymentSchedule.booking.customer', 'paymentSchedule.booking.event'])
                ->lockForUpdate()
                ->firstOrFail();

            $this->validatePayable($installment);

            if ($installment->amount_baisa === 0) {
                return $this->settleFreeInstallment($installment);
            }

            if ($installment->amount_baisa < 100) {
                throw ValidationException::withMessages([
                    'payment' => __('ui.messages.installment_amount_too_small'),
                ]);
            }

            $existingPayment = $installment->payments()
                ->where('state', PaymentState::Pending->value)
                ->whereNotNull('provider_session_id')
                ->whereNotNull('checkout_url')
                ->latest()
                ->first();

            if ($existingPayment instanceof Payment) {
                $installment->paymentSchedule->booking->seatAllocation()->update([
                    'payment_id' => $existingPayment->id,
                ]);

                return $existingPayment;
            }

            $payment = $installment->payments()->create([
                'provider' => $this->paymentGateway->name(),
                'amount_baisa' => $installment->amount_baisa,
                'currency' => $installment->paymentSchedule->currency,
            ]);

            $payload = $this->payloadFor($payment);

            $payment->forceFill([
                'request_payload' => $payload,
            ])->save();

            $installment->paymentSchedule->booking->seatAllocation()->update([
                'payment_id' => $payment->id,
            ]);

            return $payment;
        });

        if ($payment->provider_session_id && $payment->checkout_url) {
            return $payment->refresh();
        }

        if ($payment->state === PaymentState::Paid && $payment->amount_baisa === 0) {
            $this->reserveBookingSeats->execute($payment);

            return $payment->refresh();
        }

        try {
            $response = $this->paymentGateway->createSession($payment->request_payload ?? []);
            $sessionId = (string) data_get($response, 'data.session_id');
            $providerInvoice = data_get($response, 'data.invoice');
            $providerPaymentStatus = data_get($response, 'data.payment_status');

            $payment->forceFill([
                'provider_session_id' => $sessionId,
                'provider_invoice' => is_scalar($providerInvoice) ? (string) $providerInvoice : $payment->provider_invoice,
                'provider_payment_status' => is_scalar($providerPaymentStatus) ? strtolower((string) $providerPaymentStatus) : $payment->provider_payment_status,
                'checkout_url' => (string) (data_get($response, 'data.redirect_url') ?? $this->paymentGateway->checkoutUrl($sessionId)),
                'response_payload' => $response,
            ])->save();
        } catch (Throwable $exception) {
            $payment->forceFill([
                'state' => PaymentState::Failed,
                'response_payload' => $this->exceptionPayload($exception),
            ])->save();

            $this->releaseBookingSeats->execute($payment);

            throw ValidationException::withMessages([
                'payment' => __('ui.messages.payment_gateway_unavailable'),
            ]);
        }

        return $payment->refresh();
    }

    private function validatePayable(BookingInstallment $installment): void
    {
        $booking = $installment->paymentSchedule->booking;

        if ($booking->state instanceof Cancelled
            || $booking->state instanceof Rejected
            || $booking->event->status === EventStatus::Cancelled) {
            throw ValidationException::withMessages([
                'payment' => __('ui.messages.installment_not_payable'),
            ]);
        }

        if ($installment->state !== BookingInstallmentState::Pending) {
            throw ValidationException::withMessages([
                'payment' => __('ui.messages.installment_not_payable'),
            ]);
        }

        $firstPendingInstallmentId = $installment->paymentSchedule
            ->installments()
            ->where('state', BookingInstallmentState::Pending->value)
            ->orderBy('sequence')
            ->value('id');

        if ($firstPendingInstallmentId !== $installment->id) {
            throw ValidationException::withMessages([
                'payment' => __('ui.messages.pay_installments_in_order'),
            ]);
        }
    }

    private function settleFreeInstallment(BookingInstallment $installment): Payment
    {
        $settledAt = now();

        $installment->forceFill([
            'state' => BookingInstallmentState::Paid,
            'paid_at' => $settledAt,
        ])->save();

        return $installment->payments()->create([
            'provider' => PaymentProvider::Manual,
            'amount_baisa' => 0,
            'currency' => $installment->paymentSchedule->currency,
            'state' => PaymentState::Paid,
            'provider_payment_status' => 'paid',
            'request_payload' => [
                'reason' => 'zero_amount_installment',
            ],
            'response_payload' => [
                'message' => 'Settled without a payment gateway because the installment amount is zero.',
            ],
            'verified_at' => $settledAt,
            'paid_at' => $settledAt,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadFor(Payment $payment): array
    {
        $payment->loadMissing('bookingInstallment.paymentSchedule.booking.event');

        $installment = $payment->bookingInstallment;
        $booking = $installment->paymentSchedule->booking;
        $installmentName = $installment->name ?: __('ui.payments.installment_number', ['number' => $installment->sequence]);

        return [
            'client_reference_id' => $payment->reference,
            'mode' => 'payment',
            'products' => [
                [
                    'name' => Str::limit($booking->event->name.' '.$installmentName, 40, ''),
                    'quantity' => 1,
                    'unit_amount' => $payment->amount_baisa,
                ],
            ],
            'success_url' => URL::temporarySignedRoute('payments.thawani.success', now()->addHours(12), ['payment' => $payment]),
            'cancel_url' => URL::temporarySignedRoute('payments.thawani.cancel', now()->addHours(12), ['payment' => $payment]),
            'metadata' => [
                'payment_id' => $payment->id,
                'booking_id' => $booking->id,
                'installment_id' => $installment->id,
                'customer_id' => $booking->customer_id,
            ],
        ];
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
