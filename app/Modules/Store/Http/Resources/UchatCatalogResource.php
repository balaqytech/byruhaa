<?php

namespace App\Modules\Store\Http\Resources;

use App\Modules\Store\Models\Category;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Category */
class UchatCatalogResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'products' => $this->products->map(fn (Product $product): array => [
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'options' => $product->options->map(fn (ProductOption $option): array => [
                    'name' => $option->name,
                    'sku' => $option->sku,
                    'price_baisa' => $option->price_baisa,
                    'currency' => $option->currency,
                    'available' => ! $option->tracks_inventory || ($option->availableQuantity() ?? 0) > 0,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }
}
