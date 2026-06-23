<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\Concerns\FormatsApiMoney;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    use FormatsApiMoney;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_installment_id' => $this->booking_installment_id,
            'provider' => $this->provider->value,
            'reference' => $this->reference,
            'amount' => $this->money($this->amount),
            'currency' => $this->currency,
            'state' => $this->state->value,
            'state_label' => $this->state->label(),
            'provider_session_id' => $this->provider_session_id,
            'provider_payment_id' => $this->provider_payment_id,
            'provider_invoice' => $this->provider_invoice,
            'provider_payment_status' => $this->provider_payment_status,
            'checkout_url' => $this->checkout_url,
            'request_payload' => $this->providerRequestPayload(),
            'response_payload' => $this->providerResponsePayload(),
            'verified_at' => $this->verified_at?->toJSON(),
            'paid_at' => $this->paid_at?->toJSON(),
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
            'booking_installment' => $this->whenLoaded('bookingInstallment', fn (): array => [
                'id' => $this->bookingInstallment->id,
                'booking_payment_schedule_id' => $this->bookingInstallment->booking_payment_schedule_id,
                'name' => $this->bookingInstallment->name,
                'sequence' => $this->bookingInstallment->sequence,
                'percentage' => $this->bookingInstallment->percentage,
                'due_date' => $this->bookingInstallment->due_date?->toDateString(),
                'amount' => $this->money($this->bookingInstallment->amount),
                'currency' => $this->bookingInstallment->currency,
                'state' => $this->bookingInstallment->state->value,
            ]),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function providerRequestPayload(): ?array
    {
        if ($this->request_payload === null) {
            return null;
        }

        $payload = $this->request_payload;

        foreach (($payload['products'] ?? []) as $index => $product) {
            if (is_array($product) && is_numeric($product['unit_amount'] ?? null)) {
                $payload['products'][$index]['unit_amount'] = $this->money((int) $product['unit_amount'], $this->currency);
            }
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function providerResponsePayload(): ?array
    {
        if ($this->response_payload === null) {
            return null;
        }

        $payload = $this->response_payload;

        if (is_numeric(data_get($payload, 'data.total_amount'))) {
            data_set($payload, 'data.total_amount', $this->money((int) data_get($payload, 'data.total_amount'), $this->currency));
        }

        return $payload;
    }
}
