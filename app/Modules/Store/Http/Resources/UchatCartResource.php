<?php

namespace App\Modules\Store\Http\Resources;

use App\Modules\Store\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Cart */
class UchatCartResource extends JsonResource
{
    /** @var array<string, mixed>|null */
    public $quote;

    /** @param array<string, mixed> $quote */
    public function withQuote(array $quote): self
    {
        $this->quote = $quote;

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'items' => $this->items->map(fn ($item): array => [
                'sku' => $item->productOption?->sku,
                'product_name' => $item->productOption?->product?->name,
                'option_name' => $item->productOption?->name,
                'quantity' => $item->quantity,
                'note' => $item->note,
                'unit_price_baisa' => $item->productOption?->price_baisa,
                'currency' => $item->productOption?->currency,
            ])->values()->all(),
            'quote' => $this->quote ?? [],
        ];
    }
}
