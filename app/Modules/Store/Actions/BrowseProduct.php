<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Enums\ProductStatus;
use App\Modules\Store\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BrowseProduct
{
    public function execute(string $slug): Product
    {
        $product = Product::query()
            ->where('status', ProductStatus::Active->value)
            ->where('slug', $slug)
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->with([
                'category',
                'featuredImage',
                'options' => fn ($query) => $query
                    ->where('is_available', true)
                    ->where('price_baisa', '>', 0)
                    ->with([
                        'image',
                        'reservationItems' => fn ($reservationQuery) => $reservationQuery
                            ->whereHas('reservation', fn ($query) => $query
                                ->where('status', 'pending')
                                ->where('expires_at', '>', now())),
                    ]),
            ])
            ->first();

        if (! $product instanceof Product) {
            throw (new ModelNotFoundException)->setModel(Product::class, [$slug]);
        }

        $product->setRelation(
            'options',
            $product->options
                ->filter(fn ($option): bool => ! $option->tracks_inventory || ($option->availableQuantity() ?? 0) > 0)
                ->values(),
        );

        if ($product->options->isEmpty()) {
            throw (new ModelNotFoundException)->setModel(Product::class, [$slug]);
        }

        return $product;
    }
}
