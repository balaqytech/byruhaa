<?php

namespace App\Modules\Finance\Http\Resources;

use App\Http\Resources\Integrations\Uchat\FormatsUchatMoney;
use App\Modules\Finance\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Payment */
class UchatPaymentResource extends JsonResource
{
    use FormatsUchatMoney;

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
            'amount_baisa' => $this->money($this->amount),
            'currency' => $this->currency,
            'state' => $this->state->value,
            'state_label' => $this->state->label(),
            'checkout_url' => $this->checkout_url,
            'verified_at' => $this->verified_at?->toJSON(),
            'paid_at' => $this->paid_at?->toJSON(),
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
            'booking_installment' => $this->whenLoaded('bookingInstallment', function (): ?array {
                if ($this->bookingInstallment === null) {
                    return null;
                }

                return [
                    'id' => $this->bookingInstallment->id,
                    'booking_payment_schedule_id' => $this->bookingInstallment->booking_payment_schedule_id,
                    'name' => $this->bookingInstallment->name,
                    'sequence' => $this->bookingInstallment->sequence,
                    'percentage' => $this->bookingInstallment->percentage,
                    'due_date' => $this->bookingInstallment->due_date->toDateString(),
                    'amount_baisa' => $this->money($this->bookingInstallment->amount),
                    'currency' => $this->bookingInstallment->currency,
                    'state' => $this->bookingInstallment->state->value,
                ];
            }),
        ];
    }
}
