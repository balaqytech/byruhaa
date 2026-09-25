<?php

namespace App\Http\Resources\Store;

use App\Modules\Store\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrderItem */
class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_name' => $this->product_name,
            'option_name' => $this->option_name,
            'sku' => $this->sku,
            'currency' => $this->currency,
            'unit_price_baisa' => $this->unit_price_baisa,
            'regular_unit_price_baisa' => $this->regular_unit_price_baisa,
            'unit_discount_baisa' => $this->unit_discount_baisa,
            'quantity' => $this->quantity,
            'vat_baisa' => $this->vat_baisa,
            'line_subtotal_baisa' => $this->line_subtotal_baisa,
            'line_total_baisa' => $this->line_total_baisa,
            'line_discount_baisa' => $this->line_discount_baisa,
            'note' => $this->note,
        ];
    }
}
