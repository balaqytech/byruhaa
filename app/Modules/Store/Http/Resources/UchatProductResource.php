<?php

namespace App\Modules\Store\Http\Resources;

use App\Modules\Store\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class UchatProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => ['name' => $this->category?->name, 'slug' => $this->category?->slug],
            'options' => $this->options->map(fn ($option): array => [
                'name' => $option->name,
                'sku' => $option->sku,
                'price_baisa' => $option->price_baisa,
                'currency' => $option->currency,
                'available' => ! $option->tracks_inventory || ($option->availableQuantity() ?? 0) > 0,
            ])->values()->all(),
        ];
    }
}
