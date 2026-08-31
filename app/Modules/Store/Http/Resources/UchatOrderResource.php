<?php

namespace App\Modules\Store\Http\Resources;

use App\Modules\Store\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class UchatOrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'status' => $this->status->getValue(),
            'status_label' => $this->status->label(),
            'pickup_type' => $this->pickup_type,
            'pickup_at' => $this->pickup_at?->toJSON(),
            'currency' => $this->currency,
            'subtotal_baisa' => $this->subtotal_baisa,
            'vat_baisa' => $this->vat_baisa,
            'total_baisa' => $this->total_baisa,
            'customer_name' => $this->customer_name,
            'minor_profile_id' => $this->minor_profile_id,
            'recipient_name' => $this->recipient_name,
            'items' => $this->when($this->relationLoaded('items'), fn (): array => $this->items->map(fn ($item): array => [
                'sku' => $item->sku,
                'name' => $item->product_name,
                'option' => $item->option_name,
                'quantity' => $item->quantity,
                'unit_price_baisa' => $item->unit_price_baisa,
                'vat_baisa' => $item->vat_baisa,
                'line_total_baisa' => $item->line_total_baisa,
                'note' => $item->note,
            ])->values()->all()),
        ];
    }
}
