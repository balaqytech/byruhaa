<?php

namespace App\Http\Resources\Store;

use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'payment_token' => $this->payment_token,
            'status' => (string) $this->status,
            'currency' => $this->currency,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,
            'recipient_name' => $this->recipient_name,
            'recipient_phone' => $this->recipient_phone,
            'note' => $this->note,
            'pickup_type' => $this->pickup_type,
            'pickup_at' => $this->pickup_at,
            'subtotal_baisa' => $this->subtotal_baisa,
            'vat_baisa' => $this->vat_baisa,
            'total_baisa' => $this->total_baisa,
            'items' => $this->when($this->relationLoaded('items'), fn (): mixed => OrderItemResource::collection($this->items)),
            'status_history' => $this->when($this->relationLoaded('statusHistory'), fn (): array => $this->statusHistory->map(fn (OrderStatusHistory $history): array => [
                'from' => $history->from_status,
                'to' => $history->to_status,
                'note' => $history->note,
                'created_at' => $history->created_at,
            ])->values()->all()),
        ];
    }
}
