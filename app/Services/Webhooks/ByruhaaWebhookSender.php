<?php

namespace App\Services\Webhooks;

use App\Enums\BookingInstallmentState;
use App\Enums\WebhookDeliveryStatus;
use App\Models\Booking;
use App\Models\BookingFamilyMember;
use App\Models\BookingInstallment;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\WebhookDelivery;
use App\Support\Money\MoneyFactory;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Spatie\WebhookServer\WebhookCall;
use Throwable;

class ByruhaaWebhookSender
{
    public function sendCustomerRegistered(Customer $customer): void
    {
        $url = $this->webhookUrl('customer_registered_url');

        if ($url === '') {
            return;
        }

        try {
            $freshCustomer = Customer::query()->findOrFail($customer->getKey());

            $this->dispatchWebhook(
                url: $url,
                event: 'customer.registered',
                webhookable: $freshCustomer,
                payload: $this->customerPayload('customer.registered', $freshCustomer, now()),
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function sendBookingCreated(Booking $booking): void
    {
        $url = $this->webhookUrl('booking_created_url');

        if ($url === '') {
            return;
        }

        try {
            $freshBooking = Booking::query()
                ->with(['customer', 'event', 'familyMembers.familyMember', 'familyMembers.contract'])
                ->findOrFail($booking->getKey());

            $this->dispatchWebhook(
                url: $url,
                event: 'booking.created',
                webhookable: $freshBooking,
                payload: $this->bookingPayload('booking.created', $freshBooking, now()),
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function sendBookingApproved(Booking $booking): void
    {
        $url = $this->webhookUrl('booking_approved_url');

        if ($url === '') {
            return;
        }

        try {
            $freshBooking = Booking::query()
                ->with(['customer', 'event', 'familyMembers.familyMember', 'familyMembers.contract'])
                ->findOrFail($booking->getKey());

            $this->dispatchWebhook(
                url: $url,
                event: 'booking.approved',
                webhookable: $freshBooking,
                payload: $this->bookingPayload('booking.approved', $freshBooking, now()),
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function sendBookingContractsSigned(Booking $booking): void
    {
        $url = $this->webhookUrl('booking_contracts_signed_url');

        if ($url === '') {
            return;
        }

        try {
            $freshBooking = Booking::query()
                ->with(['customer', 'event', 'familyMembers.familyMember', 'familyMembers.contract'])
                ->findOrFail($booking->getKey());

            if (! $freshBooking->hasSignedContracts()) {
                return;
            }

            $payload = $this->bookingPayload('booking.contracts_signed', $freshBooking, now());
            $payload['data']['contracts'] = $this->bookingContractsPayload($freshBooking);

            $this->dispatchWebhook(
                url: $url,
                event: 'booking.contracts_signed',
                webhookable: $freshBooking,
                payload: $payload,
            );
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

        try {
            $freshPayment = Payment::query()
                ->with([
                    'bookingInstallment.paymentSchedule.booking.customer',
                    'bookingInstallment.paymentSchedule.booking.event',
                    'bookingInstallment.paymentSchedule.installments',
                ])
                ->findOrFail($payment->getKey());

            $this->dispatchWebhook(
                url: $url,
                event: 'payment.paid',
                webhookable: $freshPayment,
                payload: $this->paymentPaidPayload($freshPayment, now()),
            );
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function customerPayload(string $eventName, Customer $customer, CarbonInterface $occurredAt): array
    {
        return [
            'event' => $eventName,
            'customer_phone' => $customer->phone_number,
            'occurred_at' => $occurredAt->toJSON(),
            'data' => [
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'phone' => $customer->phone_number,
                    'email' => $customer->email,
                    'civil_id' => $customer->civil_id,
                    'wilaya' => $customer->wilaya,
                    'area' => $customer->area,
                    'address' => $customer->address,
                    'profile_complete' => $customer->hasCompleteProfile(),
                    'missing_required_profile_fields' => $customer->missingRequiredProfileFields(),
                    'customer_panel_url' => route('customer.dashboard'),
                    'created_at' => $customer->created_at?->toJSON(),
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingContractsPayload(Booking $booking): array
    {
        $contracts = $booking->familyMembers
            ->map(fn (BookingFamilyMember $bookingFamilyMember) => $bookingFamilyMember->contract)
            ->filter();
        $signedContracts = $contracts
            ->filter(fn ($contract): bool => $contract->signed_at !== null);
        $latestSignedAt = $signedContracts
            ->map(fn ($contract) => $contract->signed_at)
            ->filter()
            ->sort()
            ->last();

        return [
            'total_count' => $contracts->count(),
            'signed_count' => $signedContracts->count(),
            'all_signed' => $contracts->isNotEmpty() && $contracts->count() === $signedContracts->count(),
            'latest_signed_at' => $latestSignedAt?->toJSON(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function bookingPayload(string $eventName, Booking $booking, CarbonInterface $occurredAt): array
    {
        return [
            'event' => $eventName,
            'customer_phone' => $booking->customer?->phone_number,
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
            'customer_phone' => $booking->customer?->phone_number,
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
                'customer' => [
                    'id' => $booking->customer?->id,
                    'name' => $booking->customer?->name,
                    'phone' => $booking->customer?->phone_number,
                    'email' => $booking->customer?->email,
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
    private function dispatchWebhook(string $url, string $event, Model $webhookable, array $payload): void
    {
        $webhookCall = WebhookCall::create()
            ->url($url)
            ->payload($payload)
            ->onQueue($this->queue())
            ->timeoutInSeconds((int) config('byruhaa.webhooks.timeout', 10));

        $delivery = $this->createDelivery($webhookCall, $event, $url, $webhookable, $payload);

        if (! $delivery instanceof WebhookDelivery) {
            return;
        }

        $secret = $this->configString('byruhaa.webhooks.signing_secret');

        if ($secret !== '') {
            $webhookCall->useSecret($secret);
        } else {
            $webhookCall->doNotSign();
        }

        try {
            $webhookCall
                ->meta(['webhook_delivery_id' => $delivery->id])
                ->dispatch();

            $delivery->forceFill([
                'status' => WebhookDeliveryStatus::Queued,
                'queued_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            $delivery->forceFill([
                'status' => WebhookDeliveryStatus::Failed,
                'failed_at' => now(),
                'error_type' => $exception::class,
                'error_message' => $this->truncate($exception->getMessage()),
            ])->save();

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createDelivery(WebhookCall $webhookCall, string $event, string $url, Model $webhookable, array $payload): ?WebhookDelivery
    {
        try {
            return WebhookDelivery::query()->create([
                'uuid' => $webhookCall->getUuid(),
                'event' => $event,
                'webhook_url' => $url,
                'webhook_url_hash' => hash('sha256', $url),
                'webhookable_type' => $webhookable->getMorphClass(),
                'webhookable_id' => $webhookable->getKey(),
                'payload' => $payload,
                'status' => WebhookDeliveryStatus::Pending,
            ]);
        } catch (QueryException $exception) {
            if ($this->isUniqueConstraintViolation($exception)) {
                return null;
            }

            throw $exception;
        }
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

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['23000', '23505'], true);
    }

    private function truncate(string $value, int $limit = 10000): string
    {
        return str($value)->limit($limit, '')->toString();
    }
}
