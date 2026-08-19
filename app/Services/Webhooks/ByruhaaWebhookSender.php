<?php

namespace App\Services\Webhooks;

use App\Enums\BookingInstallmentState;
use App\Enums\WebhookDeliveryStatus;
use App\Jobs\UchatWebhookJob;
use App\Models\EventCancellation;
use App\Models\WebhookDelivery;
use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\BookingFamilyMember;
use App\Modules\Events\Models\BookingInstallment;
use App\Modules\Events\Models\EventInterest;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\PaymentRefund;
use App\Modules\Identity\Models\Customer;
use App\Modules\Store\Models\Order;
use App\Support\Money\MoneyFactory;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Spatie\WebhookServer\WebhookCall;
use Throwable;

class ByruhaaWebhookSender
{
    public function sendUchatOrderState(Order $order): void
    {
        $configuration = $this->uchatWebhookConfiguration();

        if ($configuration === null) {
            return;
        }

        [$url, $secret, $bearer] = $configuration;

        try {
            $freshOrder = Order::query()->with(['items', 'statusHistory'])->whereKey($order->getKey())->firstOrFail();
            $event = 'store.order.'.$freshOrder->status->getValue();
            $payload = [
                'event' => $event,
                'occurred_at' => now()->toJSON(),
                'customer_phone' => $freshOrder->customer_phone,
                'data' => [
                    'order' => [
                        'reference' => $freshOrder->reference,
                        'status' => $freshOrder->status->getValue(),
                        'status_label' => $freshOrder->status->label(),
                        'pickup_type' => $freshOrder->pickup_type,
                        'pickup_at' => $freshOrder->pickup_at?->toJSON(),
                        'currency' => $freshOrder->currency,
                        'subtotal_baisa' => $freshOrder->subtotal_baisa,
                        'vat_baisa' => $freshOrder->vat_baisa,
                        'total_baisa' => $freshOrder->total_baisa,
                        'items' => $freshOrder->items->map(fn ($item): array => [
                            'sku' => $item->sku,
                            'name' => $item->product_name,
                            'option' => $item->option_name,
                            'quantity' => $item->quantity,
                            'unit_price_baisa' => $item->unit_price_baisa,
                            'vat_baisa' => $item->vat_baisa,
                            'line_total_baisa' => $item->line_total_baisa,
                        ])->values()->all(),
                    ],
                    'links' => [
                        'status_url' => URL::temporarySignedRoute('store.orders.status', now()->addDays(7), ['order' => $freshOrder->payment_token]),
                        'payment_url' => $freshOrder->status->getValue() === 'pending_payment'
                            ? URL::temporarySignedRoute('store.orders.payment.store', now()->addHours(12), ['order' => $freshOrder->payment_token])
                            : null,
                    ],
                ],
            ];

            $this->dispatchUchatWebhook($url, $event, $freshOrder, $payload, $secret, $bearer);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function sendCustomerRegistered(Customer $customer): void
    {
        $url = $this->webhookUrl('customer_registered_url');

        if ($url === '') {
            return;
        }

        try {
            $freshCustomer = Customer::query()->whereKey($customer->getKey())->firstOrFail();

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

    public function sendInterestCreated(EventInterest $interest): void
    {
        $url = $this->webhookUrl('interest_created_url');

        if ($url === '') {
            return;
        }

        try {
            $freshInterest = EventInterest::query()
                ->with(['customer', 'event'])
                ->whereKey($interest->getKey())
                ->firstOrFail();

            $this->dispatchWebhook(
                url: $url,
                event: 'interest.created',
                webhookable: $freshInterest,
                payload: $this->interestPayload($freshInterest, now()),
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
                ->whereKey($booking->getKey())
                ->firstOrFail();

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
                ->whereKey($booking->getKey())
                ->firstOrFail();

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

    public function sendBookingCancelled(Booking $booking): void
    {
        $url = $this->webhookUrl('booking_cancelled_url');

        if ($url === '') {
            return;
        }

        try {
            $freshBooking = Booking::query()
                ->with(['customer', 'event', 'familyMembers.familyMember', 'familyMembers.contract'])
                ->whereKey($booking->getKey())
                ->firstOrFail();

            $this->dispatchWebhook(
                url: $url,
                event: 'booking.cancelled',
                webhookable: $freshBooking,
                payload: $this->bookingPayload('booking.cancelled', $freshBooking, now()),
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
                ->whereKey($booking->getKey())
                ->firstOrFail();

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
                ->whereKey($payment->getKey())
                ->firstOrFail();

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

    public function sendEventCancelled(EventCancellation $cancellation): void
    {
        $url = $this->webhookUrl('event_cancelled_url');

        if ($url === '') {
            return;
        }

        try {
            $cancellation = EventCancellation::query()->with('event')->findOrFail($cancellation->id);
            $this->dispatchWebhook($url, 'event.cancelled', $cancellation, [
                'event' => 'event.cancelled',
                'customer_phone' => null,
                'occurred_at' => now()->toJSON(),
                'data' => [
                    'cancellation' => [
                        'id' => $cancellation->id,
                        'status' => $cancellation->status->value,
                        'reason' => $cancellation->reason,
                        'bookings_count' => $cancellation->bookings_count,
                        'refundable_amount' => $this->money($cancellation->refundable_amount_baisa, $cancellation->currency),
                        'currency' => $cancellation->currency,
                        'requested_at' => $cancellation->requested_at->toJSON(),
                    ],
                    'event' => ['id' => $cancellation->event->id, 'name' => $cancellation->event->name, 'slug' => $cancellation->event->slug],
                ],
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function sendPaymentRefunded(PaymentRefund $refund): void
    {
        $url = $this->webhookUrl('payment_refunded_url');

        if ($url === '') {
            return;
        }

        try {
            $refund = PaymentRefund::query()->with('payment.bookingInstallment.paymentSchedule.booking.customer')->findOrFail($refund->id);
            $booking = $refund->payment->bookingInstallment->paymentSchedule->booking;
            $this->dispatchWebhook($url, 'payment.refunded', $refund, [
                'event' => 'payment.refunded',
                'customer_phone' => $booking->customer->phone_number,
                'occurred_at' => $refund->processed_at?->toJSON() ?? now()->toJSON(),
                'data' => [
                    'refund' => ['id' => $refund->id, 'reference' => $refund->reference, 'status' => $refund->state->value, 'method' => $refund->resolution_method, 'manual_reference' => $refund->manual_reference, 'amount' => $this->money($refund->amount_baisa, $refund->currency), 'currency' => $refund->currency, 'reason' => $refund->reason, 'completed_at' => $refund->processed_at?->toJSON()],
                    'payment' => ['id' => $refund->payment_id, 'reference' => $refund->payment->reference],
                    'booking' => ['id' => $booking->id, 'reference' => $booking->reference],
                    'customer' => ['id' => $booking->customer->id, 'name' => $booking->customer->name, 'phone' => $booking->customer->phone_number, 'email' => $booking->customer->email],
                ],
            ]);
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
    private function interestPayload(EventInterest $interest, CarbonInterface $occurredAt): array
    {
        return [
            'event' => 'interest.created',
            'customer_phone' => $interest->customer->phone_number,
            'occurred_at' => $occurredAt->toJSON(),
            'data' => [
                'interest' => [
                    'id' => $interest->id,
                    'status' => $interest->status->value,
                    'source' => $interest->source->value,
                    'preferred_contact_channel' => $interest->preferred_contact_channel,
                    'source_reference' => $interest->source_reference,
                    'contact_consent_at' => $interest->contact_consent_at?->toJSON(),
                    'last_expressed_at' => $interest->last_expressed_at->toJSON(),
                    'created_at' => $interest->created_at->toJSON(),
                    'customer_panel_url' => route('customer.interests.index'),
                ],
                'event' => [
                    'id' => $interest->event->id,
                    'name' => $interest->event->name,
                    'slug' => $interest->event->slug,
                    'public_url' => route('events.show', $interest->event),
                ],
                'customer' => [
                    'id' => $interest->customer->id,
                    'name' => $interest->customer->name,
                    'phone' => $interest->customer->phone_number,
                    'email' => $interest->customer->email,
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
                    ...($eventName === 'booking.cancelled' ? ['reason' => $booking->cancellation_reason] : []),
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
                    'discount_source' => $booking->discountSource(),
                    'coupon_code' => $booking->coupon_code,
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
                    'due_date' => $installment->due_date->toDateString(),
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
     * Dispatch a signed UChat delivery while retaining the shared webhook audit and queue behavior.
     *
     * @param  array<string, mixed>  $payload
     */
    private function dispatchUchatWebhook(string $url, string $event, Order $order, array $payload, string $secret, string $bearer): void
    {
        $defaultWebhookJob = config('webhook-server.webhook_job');
        Config::set('webhook-server.webhook_job', UchatWebhookJob::class);

        try {
            $webhookCall = WebhookCall::create();
        } finally {
            Config::set('webhook-server.webhook_job', $defaultWebhookJob);
        }

        $webhookCall
            ->url($url)
            ->payload($payload)
            ->withHeaders(['Authorization' => 'Bearer '.$bearer])
            ->onQueue($this->queue())
            ->timeoutInSeconds((int) config('byruhaa.webhooks.timeout', 10))
            ->useSecret($secret);

        $delivery = $this->createDelivery($webhookCall, $event, $url, $order, $payload);

        if (! $delivery instanceof WebhookDelivery) {
            return;
        }

        $payload['delivery_id'] = $delivery->id;
        $delivery->forceFill(['payload' => $payload])->save();
        $webhookCall->payload($payload);

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

    /** @return array{string, string, string}|null */
    private function uchatWebhookConfiguration(): ?array
    {
        $url = $this->configString('byruhaa.uchat.webhook_url');
        $bearer = $this->configString('byruhaa.uchat.webhook_bearer_token');
        $secret = $this->configString('byruhaa.uchat.webhook_signing_secret');

        if ($url === '' && $bearer === '' && $secret === '') {
            return null;
        }

        $missing = array_values(array_filter([
            $url === '' ? 'UCHAT_STORE_WEBHOOK_URL' : null,
            $bearer === '' ? 'UCHAT_STORE_WEBHOOK_BEARER_TOKEN' : null,
            $secret === '' ? 'UCHAT_STORE_WEBHOOK_SIGNING_SECRET' : null,
        ]));

        if ($missing !== []) {
            Log::warning('UChat Store webhook is disabled because its configuration is incomplete.', ['missing' => $missing]);

            return null;
        }

        if (in_array(config('app.env'), ['staging', 'production'], true) && ! str_starts_with(strtolower($url), 'https://')) {
            Log::warning('UChat Store webhook is disabled because staging and production require HTTPS.');

            return null;
        }

        return [$url, $secret, $bearer];
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
