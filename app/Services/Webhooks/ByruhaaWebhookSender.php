<?php

namespace App\Services\Webhooks;

use App\Enums\BookingInstallmentState;
use App\Models\Booking;
use App\Models\BookingFamilyMember;
use App\Models\BookingInstallment;
use App\Models\Payment;
use App\Support\Money\MoneyFactory;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Spatie\WebhookServer\WebhookCall;
use Throwable;

class ByruhaaWebhookSender
{
    public function sendBookingApproved(Booking $booking): void
    {
        $url = $this->webhookUrl('booking_approved_url');

        if ($url === '') {
            return;
        }

        $sentAt = now();

        try {
            $claimed = Booking::query()
                ->whereKey($booking->getKey())
                ->whereNull('booking_approved_webhook_sent_at')
                ->update(['booking_approved_webhook_sent_at' => $sentAt]);

            if ($claimed === 0) {
                return;
            }

            $freshBooking = Booking::query()
                ->with(['customer', 'event', 'familyMembers.familyMember', 'familyMembers.contract'])
                ->findOrFail($booking->getKey());

            $this->dispatch($url, $this->bookingApprovedPayload($freshBooking, $sentAt));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function sendPaymentPaid(Payment $payment): void
    {
        $url = $this->webhookUrl('payment_paid_url');

        if ($url === '') {
            return;
        }

        $sentAt = now();

        try {
            $claimed = Payment::query()
                ->whereKey($payment->getKey())
                ->whereNull('payment_paid_webhook_sent_at')
                ->update(['payment_paid_webhook_sent_at' => $sentAt]);

            if ($claimed === 0) {
                return;
            }

            $freshPayment = Payment::query()
                ->with([
                    'bookingInstallment.paymentSchedule.booking.customer',
                    'bookingInstallment.paymentSchedule.booking.event',
                    'bookingInstallment.paymentSchedule.installments',
                ])
                ->findOrFail($payment->getKey());

            $this->dispatch($url, $this->paymentPaidPayload($freshPayment, $sentAt));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingApprovedPayload(Booking $booking, CarbonInterface $occurredAt): array
    {
        return [
            'event' => 'booking.approved',
            'occurred_at' => $occurredAt->toJSON(),
            'data' => [
                'booking' => [
                    'id' => $booking->id,
                    'reference' => $booking->reference,
                    'status' => $booking->state->getValue(),
                    'customer_panel_url' => route('customer.bookings.show', $booking),
                ],
                'event' => [
                    'id' => $booking->event?->id,
                    'name' => $booking->event?->name,
                ],
                'customer' => [
                    'id' => $booking->customer?->id,
                    'name' => $booking->customer?->name,
                    'phone' => $booking->customer?->phone_number,
                    'email' => $booking->customer?->email,
                ],
                'participants' => $booking->familyMembers
                    ->map(fn (BookingFamilyMember $bookingFamilyMember): array => [
                        'booking_family_member_id' => $bookingFamilyMember->id,
                        'family_member_id' => $bookingFamilyMember->family_member_id,
                        'name' => $bookingFamilyMember->familyMember?->name,
                        'contract_id' => $bookingFamilyMember->contract?->id,
                        'contract_status' => $bookingFamilyMember->contract?->state?->getValue(),
                        'contract_signed_at' => $bookingFamilyMember->contract?->signed_at?->toJSON(),
                    ])
                    ->values()
                    ->all(),
                'pricing' => [
                    'unit_price' => $this->money($booking->unit_price_baisa, $booking->currency),
                    'subtotal' => $this->money($booking->subtotal_baisa, $booking->currency),
                    'discount_amount' => $this->money($booking->discount_amount_baisa, $booking->currency),
                    'total' => $this->money($booking->total_baisa, $booking->currency),
                    'currency' => $booking->currency,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentPaidPayload(Payment $payment, CarbonInterface $occurredAt): array
    {
        $schedule = $payment->bookingInstallment->paymentSchedule;
        $booking = $schedule->booking;
        $installments = $schedule->installments;
        $paidInstallments = $installments
            ->filter(fn (BookingInstallment $installment): bool => $installment->state === BookingInstallmentState::Paid);
        $paymentStatus = $paidInstallments->count() === $installments->count()
            ? 'paid'
            : 'partially_paid';

        $payload = [
            'event' => 'payment.paid',
            'occurred_at' => $occurredAt->toJSON(),
            'data' => [
                'payment' => [
                    'id' => $payment->id,
                    'provider' => $payment->provider->value,
                    'status' => $payment->state->value,
                    'provider_status' => $payment->provider_payment_status,
                    'amount' => $this->money($payment->amount_baisa, $payment->currency),
                    'currency' => $payment->currency,
                    'paid_at' => $payment->paid_at?->toJSON(),
                    'provider_reference' => $payment->provider_payment_id
                        ?: $payment->provider_invoice
                            ?: $payment->provider_session_id,
                ],
                'booking' => [
                    'id' => $booking->id,
                    'reference' => $booking->reference,
                    'status' => $booking->state->getValue(),
                    'payment_status' => $paymentStatus,
                    'customer_panel_url' => route('customer.bookings.show', $booking),
                ],
            ],
        ];

        if ($paymentStatus === 'partially_paid') {
            $payload['data']['installments'] = $this->partialInstallmentPayload($installments, $paidInstallments, $schedule->currency);
        }

        return $payload;
    }

    /**
     * @param  Collection<int, BookingInstallment>  $installments
     * @param  Collection<int, BookingInstallment>  $paidInstallments
     * @return array<string, mixed>
     */
    private function partialInstallmentPayload(Collection $installments, Collection $paidInstallments, string $currency): array
    {
        $paidAmountBaisa = (int) $paidInstallments->sum('amount_baisa');
        $totalAmountBaisa = (int) $installments->sum('amount_baisa');

        return [
            'paid_installments_count' => $paidInstallments->count(),
            'total_installments_count' => $installments->count(),
            'paid_amount' => $this->money($paidAmountBaisa, $currency),
            'remaining_amount' => $this->money(max(0, $totalAmountBaisa - $paidAmountBaisa), $currency),
            'currency' => $currency,
            'items' => $installments
                ->map(fn (BookingInstallment $installment): array => [
                    'id' => $installment->id,
                    'name' => $installment->name,
                    'sequence' => $installment->sequence,
                    'percentage' => $installment->percentage,
                    'due_date' => $installment->due_date?->toDateString(),
                    'status' => $installment->state->value,
                    'amount' => $this->money($installment->amount_baisa, $installment->currency),
                    'currency' => $installment->currency,
                    'paid_at' => $installment->paid_at?->toJSON(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function dispatch(string $url, array $payload): void
    {
        $webhookCall = WebhookCall::create()
            ->url($url)
            ->payload($payload)
            ->onQueue($this->queue())
            ->timeoutInSeconds((int) config('byruhaa.webhooks.timeout', 10));

        $secret = $this->configString('byruhaa.webhooks.signing_secret');

        if ($secret !== '') {
            $webhookCall->useSecret($secret);
        } else {
            $webhookCall->doNotSign();
        }

        $webhookCall->dispatch();
    }

    private function money(int $amountBaisa, string $currency): string
    {
        return MoneyFactory::formatMinorUnits($amountBaisa, $currency);
    }

    private function webhookUrl(string $key): string
    {
        return $this->configString("byruhaa.webhooks.{$key}");
    }

    private function queue(): ?string
    {
        $queue = $this->configString('byruhaa.webhooks.queue', 'default');

        return $queue === '' ? null : $queue;
    }

    private function configString(string $key, ?string $default = null): string
    {
        $value = config($key, $default);

        return is_scalar($value) ? trim((string) $value) : '';
    }
}
