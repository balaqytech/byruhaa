<?php

namespace App\Http\Resources\Store;

use App\Modules\Store\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CartItem */
class CartItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_option_id' => $this->product_option_id,
            'quantity' => $this->quantity,
            'note' => $this->note,
            'product_option' => $this->when($this->relationLoaded('productOption'), fn (): array => [
                'name' => $this->productOption?->name,
                'sku' => $this->productOption?->sku,
                'price_baisa' => $this->productOption?->price_baisa,
                'currency' => $this->productOption?->currency,
                'product' => $this->when($this->productOption?->relationLoaded('product'), fn (): array => [
                    'name' => $this->productOption->product?->name,
                ]),
            ]),
        ];
    }
}
