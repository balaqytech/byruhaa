<?php

namespace App\Actions;

use App\Enums\BookingInstallmentState;
use App\Enums\PaymentState;
use App\Models\BookingInstallment;
use App\Models\Payment;
use App\Services\Thawani\ThawaniClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class InitiateInstallmentPayment
{
    public function __construct(private ThawaniClient $thawaniClient) {}

    public function execute(BookingInstallment $installment, int $customerId): Payment
    {
        $payment = DB::transaction(function () use ($installment, $customerId): Payment {
            $installment = BookingInstallment::query()
                ->whereKey($installment->id)
                ->whereHas('paymentSchedule.booking', fn ($query) => $query->where('customer_id', $customerId))
                ->with(['paymentSchedule.booking.event'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($installment->state !== BookingInstallmentState::Pending) {
                throw ValidationException::withMessages([
                    'payment' => __('ui.messages.installment_not_payable'),
                ]);
            }

            if ($installment->amount_baisa < 100) {
                throw ValidationException::withMessages([
                    'payment' => __('ui.messages.installment_amount_too_small'),
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

            $payment = $installment->payments()->create([
                'amount_baisa' => $installment->amount_baisa,
                'currency' => $installment->paymentSchedule->currency,
            ]);

            $payload = $this->payloadFor($payment);

            $payment->forceFill([
                'request_payload' => $payload,
            ])->save();

            return $payment;
        });

        try {
            $response = $this->thawaniClient->createSession($payment->request_payload ?? []);
            $sessionId = (string) data_get($response, 'data.session_id');

            $payment->forceFill([
                'provider_session_id' => $sessionId,
                'checkout_url' => $this->thawaniClient->checkoutUrl($sessionId),
                'response_payload' => $response,
            ])->save();
        } catch (Throwable $exception) {
            $payment->forceFill([
                'state' => PaymentState::Failed,
                'response_payload' => [
                    'error' => $exception->getMessage(),
                ],
            ])->save();

            throw ValidationException::withMessages([
                'payment' => __('ui.messages.payment_gateway_unavailable'),
            ]);
        }

        return $payment->refresh();
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
}
