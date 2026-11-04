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
        $quoteItems = [];
        $rawQuoteItems = $this->quote['items'] ?? [];

        if (is_array($rawQuoteItems)) {
            foreach ($rawQuoteItems as $quoteItem) {
                if (is_array($quoteItem) && is_string($quoteItem['sku'] ?? null)) {
                    $quoteItems[$quoteItem['sku']] = $quoteItem;
                }
            }
        }

        return [
            'items' => $this->items->map(function ($item) use ($quoteItems): array {
                $sku = $item->productOption?->sku;
                $quoteItem = $quoteItems[$sku] ?? [];

                return [
                    'sku' => $sku,
                    'product_name' => $item->productOption?->product?->name,
                    'option_name' => $item->productOption?->name,
                    'quantity' => $item->quantity,
                    'note' => $item->note,
                    'unit_price_baisa' => $quoteItem['unit_price_baisa'] ?? $item->productOption?->price_baisa,
                    'regular_unit_price_baisa' => $quoteItem['regular_unit_price_baisa'] ?? $item->productOption?->price_baisa,
                    'unit_discount_baisa' => $quoteItem['unit_discount_baisa'] ?? 0,
                    'line_subtotal_baisa' => $quoteItem['line_subtotal_baisa'] ?? null,
                    'vat_baisa' => $quoteItem['vat_baisa'] ?? null,
                    'line_total_baisa' => $quoteItem['line_total_baisa'] ?? null,
                    'line_discount_baisa' => $quoteItem['line_discount_baisa'] ?? 0,
                    'currency' => $item->productOption?->currency,
                ];
            })->values()->all(),
            'quote' => $this->quote ?? [],
        ];
    }
}
