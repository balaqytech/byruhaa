<?php

namespace App\Services\Webhooks;

use App\Enums\BookingInstallmentState;
use App\Models\EventCancellation;
use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\BookingInstallment;
use App\Modules\Events\Models\EventInterest;
use App\Modules\Events\Webhooks\EventWebhookPayload;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\PaymentRefund;
use App\Modules\Finance\Models\WalletMovement;
use App\Modules\Finance\Models\WalletTopUp;
use App\Modules\Finance\Models\WalletWithdrawalRequest;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Identity\Webhooks\CustomerWebhookPayload;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\OrderRefundRequest;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

class ByruhaaWebhookSender
{
    public function __construct(private UchatWebhookTransport $transport, private CustomerWebhookPayload $customers, private EventWebhookPayload $events) {}

    public function canSendUchatCustomerPhoneVerificationCode(): bool
    {
        return $this->uchatWebhookConfiguration() !== null;
    }

    public function sendUchatCustomerPhoneVerificationCode(Customer $customer, string $code, CarbonInterface $expiresAt, ?string $deliveryKey = null): void
    {
        $configuration = $this->uchatWebhookConfiguration();

        if ($configuration === null) {
            return;
        }

        [$url, $secret, $bearer] = $configuration;

        $payload = [
            'event' => 'customer.phone.verification_code',
            'occurred_at' => now()->toJSON(),
            'customer_phone' => $customer->phone_number,
            'data' => [
                'customer' => ['id' => $customer->id, 'name' => $customer->name],
                'verification' => ['code' => $code, 'expires_at' => $expiresAt->toJSON()],
            ],
        ];
        $auditPayload = $payload;
        $auditPayload['data']['verification']['code'] = '[redacted]';

        $this->dispatchUchatWebhook($url, 'customer.phone.verification_code', $customer, $payload, $secret, $bearer, $auditPayload, $deliveryKey);
    }

    public function sendUchatMinorVerificationCode(MinorProfile $profile, string $code, ?string $deliveryKey = null): void
    {
        $configuration = $this->uchatWebhookConfiguration();

        if ($configuration === null) {
            return;
        }

        [$url, $secret, $bearer] = $configuration;

        $profile->loadMissing('familyMember.customer');
        $payload = [
            'event' => 'minor.guardian.verification_code',
            'occurred_at' => now()->toJSON(),
            'customer_phone' => $profile->familyMember->customer->phone_number,
            'data' => [
                'minor_profile' => [
                    'id' => $profile->id,
                    'member_code' => $profile->member_code,
                    'name' => $profile->familyMember->name,
                ],
                'verification' => [
                    'code' => $code,
                    'expires_at' => now()->addMinutes((int) config('byruhaa.minor_accounts.otp_expiry_minutes', 10))->toJSON(),
                ],
            ],
        ];

        $auditPayload = $payload;
        $auditPayload['data']['verification']['code'] = '[redacted]';

        $this->dispatchUchatWebhook($url, 'minor.guardian.verification_code', $profile, $payload, $secret, $bearer, $auditPayload, $deliveryKey);
    }

    public function sendUchatOrderState(Order $order): void
    {
        $configuration = $this->uchatWebhookConfiguration();

        if ($configuration === null) {
            return;
        }

        [$url, $secret, $bearer] = $configuration;

        $freshOrder = Order::query()->with(['items', 'statusHistory'])->whereKey($order->getKey())->firstOrFail();
        $minorProfile = $freshOrder->minor_profile_id === null
            ? null
            : MinorProfile::query()->with('familyMember')->find($freshOrder->minor_profile_id);
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
                    'minor_profile_id' => $freshOrder->minor_profile_id,
                    'payment_method' => $freshOrder->payment_method,
                    'minor_name' => $minorProfile?->familyMember?->name,
                    'pickup_type' => $freshOrder->pickup_type,
                    'pickup_at' => $freshOrder->pickup_at?->toJSON(),
                    'currency' => $freshOrder->currency,
                    'subtotal_baisa' => $freshOrder->subtotal_baisa,
                    'vat_baisa' => $freshOrder->vat_baisa,
                    'total_baisa' => $freshOrder->total_baisa,
                    'regular_total_baisa' => $freshOrder->regular_total_baisa,
                    'discount_baisa' => $freshOrder->discount_baisa,
                    'pricing_tier' => $freshOrder->pricing_tier,
                    'items' => $freshOrder->items->map(fn ($item): array => [
                        'sku' => $item->sku,
                        'name' => $item->product_name,
                        'option' => $item->option_name,
                        'quantity' => $item->quantity,
                        'unit_price_baisa' => $item->unit_price_baisa,
                        'regular_unit_price_baisa' => $item->regular_unit_price_baisa,
                        'unit_discount_baisa' => $item->unit_discount_baisa,
                        'vat_baisa' => $item->vat_baisa,
                        'line_total_baisa' => $item->line_total_baisa,
                        'line_discount_baisa' => $item->line_discount_baisa,
                    ])->values()->all(),
                ],
                'links' => [
                    'status_url' => URL::temporarySignedRoute('store.orders.status', now()->addDays(7), ['order' => $freshOrder->payment_token]),
                    'payment_url' => $freshOrder->status->getValue() === 'pending_payment' && $freshOrder->payment_method === 'thawani'
                        ? URL::temporarySignedRoute('store.orders.payment.store', now()->addHours(12), ['order' => $freshOrder->payment_token])
                        : null,
                    'wallet_confirmation_url' => null,
                ],
            ],
        ];

        $this->dispatchUchatWebhook($url, $event, $freshOrder, $payload, $secret, $bearer, null, 'order-transition:'.($freshOrder->statusHistory->first()->id ?? 'initial'));
    }

    public function sendUchatWalletMovement(WalletMovement $movement): void
    {
        $configuration = $this->uchatWebhookConfiguration();

        if ($configuration === null) {
            return;
        }

        [$url, $secret, $bearer] = $configuration;

        $movement = WalletMovement::query()->with('wallet')->findOrFail($movement->id);
        $profile = MinorProfile::query()->with('familyMember.customer')->find($movement->wallet->minor_profile_id);

        if (! $profile instanceof MinorProfile) {
            return;
        }

        $payload = [
            'event' => 'wallet.'.$movement->type,
            'occurred_at' => $movement->created_at?->toJSON() ?? now()->toJSON(),
            'customer_phone' => $profile->familyMember->customer->phone_number,
            'data' => [
                'wallet' => [
                    'id' => $movement->wallet_id,
                    'minor_profile_id' => $profile->id,
                    'balance_baisa' => $movement->balance_after_baisa,
                    'currency' => $movement->wallet->currency,
                    'status' => $movement->wallet->status,
                ],
                'movement' => [
                    'id' => $movement->id,
                    'type' => $movement->type,
                    'order_reference' => $movement->order_reference,
                    'credit_baisa' => $movement->credit_baisa,
                    'debit_baisa' => $movement->debit_baisa,
                    'balance_after_baisa' => $movement->balance_after_baisa,
                    'created_at' => $movement->created_at?->toJSON(),
                ],
            ],
        ];

        $this->dispatchUchatWebhook($url, $payload['event'], $movement->wallet, $payload, $secret, $bearer, null, $movement->operation_key);
    }

    public function sendUchatWalletWithdrawal(WalletWithdrawalRequest $withdrawal): void
    {
        $configuration = $this->uchatWebhookConfiguration();
        if ($configuration === null) {
            return;
        }
        [$url, $secret, $bearer] = $configuration;

        $withdrawal = WalletWithdrawalRequest::query()->with('guardian')->findOrFail($withdrawal->id);
        $payload = [
            'event' => 'wallet.withdrawal.'.$withdrawal->status,
            'occurred_at' => $withdrawal->updated_at?->toJSON() ?? now()->toJSON(),
            'customer_phone' => $withdrawal->guardian->phone_number,
            'data' => [
                'reference' => $withdrawal->reference,
                'minor_profile_id' => $withdrawal->wallet->minor_profile_id,
                'amount_baisa' => $withdrawal->amount_baisa,
                'currency' => $withdrawal->currency,
                'method' => $withdrawal->method,
                'status' => $withdrawal->status,
                'payout_reference' => $withdrawal->status === 'paid' ? $withdrawal->payout_reference : null,
            ],
        ];
        $this->dispatchUchatWebhook($url, $payload['event'], $withdrawal, $payload, $secret, $bearer, null, 'withdrawal:'.$withdrawal->id.':'.$withdrawal->status.':'.$withdrawal->updated_at?->format('YmdHisu'));
    }

    public function sendUchatOrderRefund(OrderRefundRequest $refund): void
    {
        $configuration = $this->uchatWebhookConfiguration();
        if ($configuration === null) {
            return;
        }
        [$url, $secret, $bearer] = $configuration;

        $refund = OrderRefundRequest::query()->with('order')->findOrFail($refund->id);
        $payload = [
            'event' => 'store.refund.'.$refund->status,
            'occurred_at' => $refund->updated_at?->toJSON() ?? now()->toJSON(),
            'customer_phone' => $refund->order->customer_phone,
            'data' => [
                'reference' => $refund->reference,
                'order_reference' => $refund->order->reference,
                'amount_baisa' => $refund->amount_baisa,
                'currency' => $refund->currency,
                'status' => $refund->status,
                'payment_method' => $refund->order->payment_method,
                'payout_reference' => $refund->status === 'paid' ? $refund->payout_reference : null,
            ],
        ];
        $this->dispatchUchatWebhook($url, $payload['event'], $refund, $payload, $secret, $bearer, null, 'store-refund:'.$refund->id.':'.$refund->status.':'.$refund->updated_at?->format('YmdHisu'));
    }

    public function sendCustomerRegistered(Customer $customer): void
    {
        $url = $this->webhookUrl('customer_registered_url');

        if ($url === '') {
            return;
        }

        $freshCustomer = Customer::query()->whereKey($customer->getKey())->firstOrFail();

        $this->dispatchWebhook(
            url: $url,
            event: 'customer.registered',
            webhookable: $freshCustomer,
            payload: $this->customers->customerPayload('customer.registered', $freshCustomer, now()),
        );
    }

    public function sendInterestCreated(EventInterest $interest): void
    {
        $url = $this->webhookUrl('interest_created_url');

        if ($url === '') {
            return;
        }

        $freshInterest = EventInterest::query()
            ->with(['customer', 'event'])
            ->whereKey($interest->getKey())
            ->firstOrFail();

        $this->dispatchWebhook(
            url: $url,
            event: 'interest.created',
            webhookable: $freshInterest,
            payload: $this->events->interestPayload($freshInterest, now()),
        );
    }

    public function sendBookingCreated(Booking $booking): void
    {
        $url = $this->webhookUrl('booking_created_url');

        if ($url === '') {
            return;
        }

        $freshBooking = Booking::query()
            ->with(['customer', 'event', 'familyMembers.familyMember', 'familyMembers.contract'])
            ->whereKey($booking->getKey())
            ->firstOrFail();

        $this->dispatchWebhook(
            url: $url,
            event: 'booking.created',
            webhookable: $freshBooking,
            payload: $this->events->bookingPayload('booking.created', $freshBooking, now()),
        );
    }

    public function sendBookingApproved(Booking $booking): void
    {
        $url = $this->webhookUrl('booking_approved_url');

        if ($url === '') {
            return;
        }

        $freshBooking = Booking::query()
            ->with(['customer', 'event', 'familyMembers.familyMember', 'familyMembers.contract'])
            ->whereKey($booking->getKey())
            ->firstOrFail();

        $this->dispatchWebhook(
            url: $url,
            event: 'booking.approved',
            webhookable: $freshBooking,
            payload: $this->events->bookingPayload('booking.approved', $freshBooking, now()),
        );
    }

    public function sendBookingCancelled(Booking $booking): void
    {
        $url = $this->webhookUrl('booking_cancelled_url');

        if ($url === '') {
            return;
        }

        $freshBooking = Booking::query()
            ->with(['customer', 'event', 'familyMembers.familyMember', 'familyMembers.contract'])
            ->whereKey($booking->getKey())
            ->firstOrFail();

        $this->dispatchWebhook(
            url: $url,
            event: 'booking.cancelled',
            webhookable: $freshBooking,
            payload: $this->events->bookingPayload('booking.cancelled', $freshBooking, now()),
        );
    }

    public function sendBookingContractsSigned(Booking $booking): void
    {
        $url = $this->webhookUrl('booking_contracts_signed_url');

        if ($url === '') {
            return;
        }

        $freshBooking = Booking::query()
            ->with(['customer', 'event', 'familyMembers.familyMember', 'familyMembers.contract'])
            ->whereKey($booking->getKey())
            ->firstOrFail();

        if (! $freshBooking->hasSignedContracts()) {
            return;
        }

        $payload = $this->events->bookingPayload('booking.contracts_signed', $freshBooking, now());
        $payload['data']['contracts'] = $this->events->bookingContractsPayload($freshBooking);

        $this->dispatchWebhook(
            url: $url,
            event: 'booking.contracts_signed',
            webhookable: $freshBooking,
            payload: $payload,
        );
    }

    public function sendPaymentPaid(Payment $payment): void
    {
        $url = $this->webhookUrl('payment_paid_url');

        if ($url === '') {
            return;
        }

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
    }

    public function sendEventCancelled(EventCancellation $cancellation): void
    {
        $url = $this->webhookUrl('event_cancelled_url');

        if ($url === '') {
            return;
        }

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
                    'refundable_amount_baisa' => $this->money($cancellation->refundable_amount_baisa, $cancellation->currency),
                    'currency' => $cancellation->currency,
                    'requested_at' => $cancellation->requested_at->toJSON(),
                ],
                'event' => ['id' => $cancellation->event->id, 'name' => $cancellation->event->name, 'slug' => $cancellation->event->slug],
            ],
        ]);
    }

    public function sendEventCancellationOutcome(EventCancellation $cancellation): void
    {
        $cancellation->loadMissing('event');
        $event = 'event.cancellation.'.$cancellation->status->value;
        $this->transport->record($cancellation, [
            'event' => $event,
            'customer_phone' => null,
            'occurred_at' => now()->toJSON(),
            'data' => [
                'cancellation' => [
                    'id' => $cancellation->id,
                    'status' => $cancellation->status->value,
                    'bookings_count' => $cancellation->bookings_count,
                    'refunded_payments_count' => $cancellation->refunded_payments_count,
                    'refunded_amount_baisa' => $cancellation->refunded_amount_baisa,
                    'currency' => $cancellation->currency,
                    'completed_at' => $cancellation->completed_at?->toJSON(),
                ],
                'event' => ['id' => $cancellation->event->id, 'slug' => $cancellation->event->slug, 'name' => $cancellation->event->name],
            ],
        ]);
    }

    public function sendPaymentRefunded(PaymentRefund $refund): void
    {
        $url = $this->webhookUrl('payment_refunded_url');

        if ($url === '') {
            return;
        }

        $refund = PaymentRefund::query()->with('payment.bookingInstallment.paymentSchedule.booking.customer')->findOrFail($refund->id);
        $booking = $refund->payment->bookingInstallment?->paymentSchedule?->booking;

        if ($booking === null) {
            $walletTopUp = WalletTopUp::query()->where('reference', $refund->payment->subject_reference)->first();
            $profile = $walletTopUp?->wallet_id === null
                ? null
                : MinorProfile::query()->with('familyMember.customer')->find($walletTopUp->wallet->minor_profile_id);

            $this->dispatchWebhook($url, 'payment.refunded', $refund, [
                'event' => 'payment.refunded',
                'customer_phone' => $profile?->familyMember?->customer?->phone_number,
                'occurred_at' => $refund->processed_at?->toJSON() ?? now()->toJSON(),
                'data' => [
                    'refund' => ['id' => $refund->id, 'reference' => $refund->reference, 'status' => $refund->state->value, 'method' => $refund->resolution_method, 'manual_reference' => $refund->manual_reference, 'amount_baisa' => $this->money($refund->amount_baisa, $refund->currency), 'currency' => $refund->currency, 'reason' => $refund->reason, 'completed_at' => $refund->processed_at?->toJSON()],
                    'payment' => ['id' => $refund->payment_id, 'reference' => $refund->payment->reference],
                    'wallet_top_up' => ['reference' => $refund->payment->subject_reference],
                    'customer' => $profile === null ? null : ['id' => $profile->familyMember->customer->id, 'name' => $profile->familyMember->customer->name, 'phone' => $profile->familyMember->customer->phone_number, 'email' => $profile->familyMember->customer->email],
                ],
            ]);

            return;
        }

        $this->dispatchWebhook($url, 'payment.refunded', $refund, [
            'event' => 'payment.refunded',
            'customer_phone' => $booking->customer->phone_number,
            'occurred_at' => $refund->processed_at?->toJSON() ?? now()->toJSON(),
            'data' => [
                'refund' => ['id' => $refund->id, 'reference' => $refund->reference, 'status' => $refund->state->value, 'method' => $refund->resolution_method, 'manual_reference' => $refund->manual_reference, 'amount_baisa' => $this->money($refund->amount_baisa, $refund->currency), 'currency' => $refund->currency, 'reason' => $refund->reason, 'completed_at' => $refund->processed_at?->toJSON()],
                'payment' => ['id' => $refund->payment_id, 'reference' => $refund->payment->reference],
                'booking' => ['id' => $booking->id, 'reference' => $booking->reference],
                'customer' => ['id' => $booking->customer->id, 'name' => $booking->customer->name, 'phone' => $booking->customer->phone_number, 'email' => $booking->customer->email],
            ],
        ]);
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
                    'reference' => $payment->reference,
                    'provider' => $payment->provider->value,
                    'status' => $payment->state->value,
                    'provider_status' => $payment->provider_payment_status,
                    'amount_baisa' => $this->money($payment->amount_baisa, $payment->currency),
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
            'paid_amount_baisa' => $this->money($paidAmountBaisa, $currency),
            'remaining_amount_baisa' => $this->money(max(0, $totalAmountBaisa - $paidAmountBaisa), $currency),
            'currency' => $currency,
            'items' => $installments
                ->map(fn (BookingInstallment $installment): array => [
                    'id' => $installment->id,
                    'name' => $installment->name,
                    'sequence' => $installment->sequence,
                    'percentage' => $installment->percentage,
                    'due_date' => $installment->due_date->toDateString(),
                    'status' => $installment->state->value,
                    'amount_baisa' => $this->money($installment->amount_baisa, $installment->currency),
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
        $this->transport->record($webhookable, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>|null  $auditPayload
     */
    private function dispatchUchatWebhook(string $url, string $event, Model $webhookable, array $payload, string $secret, string $bearer, ?array $auditPayload = null, ?string $deliveryKey = null): void
    {
        $this->transport->record($webhookable, $payload, $auditPayload, $deliveryKey);
    }

    private function money(int $amountBaisa, string $currency): int
    {
        return $amountBaisa;
    }

    private function webhookUrl(string $key): string
    {
        return $this->transport->configuration()[0] ?? '';
    }

    /** @return array{string, string, string}|null */
    private function uchatWebhookConfiguration(): ?array
    {
        return $this->transport->configuration();
    }
}
