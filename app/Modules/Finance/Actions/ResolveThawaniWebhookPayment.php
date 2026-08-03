<?php

namespace App\Modules\Finance\Actions;

use App\Enums\PaymentProvider;
use App\Modules\Finance\Models\Payment;
use Illuminate\Database\Eloquent\Builder;

class ResolveThawaniWebhookPayment
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function resolve(array $payload): ?Payment
    {
        $identifiers = $this->identifiers($payload);

        if ($identifiers['client_reference_id'] !== null) {
            $payment = $this->query()
                ->where('reference', $identifiers['client_reference_id'])
                ->first();

            if ($payment instanceof Payment) {
                return $payment;
            }
        }

        if ($identifiers['provider_session_id'] !== null) {
            $payment = $this->query()
                ->where('provider_session_id', $identifiers['provider_session_id'])
                ->first();

            if ($payment instanceof Payment) {
                return $payment;
            }
        }

        if ($identifiers['provider_invoice'] !== null) {
            $payment = $this->query()
                ->where('provider_invoice', $identifiers['provider_invoice'])
                ->first();

            if ($payment instanceof Payment) {
                return $payment;
            }
        }

        if ($identifiers['provider_payment_id'] !== null) {
            $payment = $this->query()
                ->where('provider_payment_id', $identifiers['provider_payment_id'])
                ->first();

            if ($payment instanceof Payment) {
                return $payment;
            }
        }

        if ($identifiers['metadata_payment_id'] !== null) {
            return $this->query()
                ->whereKey($identifiers['metadata_payment_id'])
                ->first();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     event_type: string|null,
     *     provider_event_id: string|null,
     *     client_reference_id: string|null,
     *     provider_session_id: string|null,
     *     provider_payment_id: string|null,
     *     provider_invoice: string|null,
     *     metadata_payment_id: int|null
     * }
     */
    public function identifiers(array $payload): array
    {
        return [
            'event_type' => $this->stringFrom($payload, ['event_type', 'event', 'type', 'data.event_type', 'data.event', 'data.type']),
            'provider_event_id' => $this->stringFrom($payload, ['event_id', 'webhook_id', 'id', 'data.event_id', 'data.webhook_id', 'data.id']),
            'client_reference_id' => $this->stringFrom($payload, ['client_reference_id', 'data.client_reference_id', 'checkout.client_reference_id', 'data.checkout.client_reference_id', 'session.client_reference_id', 'data.session.client_reference_id']),
            'provider_session_id' => $this->stringFrom($payload, ['provider_session_id', 'session_id', 'checkout_session_id', 'data.provider_session_id', 'data.session_id', 'data.checkout_session_id', 'checkout.session_id', 'data.checkout.session_id', 'session.session_id', 'data.session.session_id']),
            'provider_payment_id' => $this->stringFrom($payload, ['provider_payment_id', 'payment_id', 'data.provider_payment_id', 'data.payment_id', 'payment.payment_id', 'data.payment.payment_id', 'payment.id', 'data.payment.id']),
            'provider_invoice' => $this->stringFrom($payload, ['provider_invoice', 'invoice', 'checkout_invoice', 'data.provider_invoice', 'data.invoice', 'data.checkout_invoice', 'checkout.invoice', 'data.checkout.invoice', 'session.invoice', 'data.session.invoice']),
            'metadata_payment_id' => $this->integerFrom($payload, ['metadata.payment_id', 'data.metadata.payment_id', 'checkout.metadata.payment_id', 'data.checkout.metadata.payment_id', 'session.metadata.payment_id', 'data.session.metadata.payment_id']),
        ];
    }

    /**
     * @return Builder<Payment>
     */
    private function query(): Builder
    {
        return Payment::query()->where('provider', PaymentProvider::Thawani->value);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $keys
     */
    private function stringFrom(array $payload, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = data_get($payload, $key);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return (string) $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $keys
     */
    private function integerFrom(array $payload, array $keys): ?int
    {
        foreach ($keys as $key) {
            $value = data_get($payload, $key);

            if (is_numeric($value) && (int) $value > 0) {
                return (int) $value;
            }
        }

        return null;
    }
}
