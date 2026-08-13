<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Models\Category;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use Illuminate\Database\Eloquent\Collection;

class BrowseCatalog
{
    /**
     * @return Collection<int, Category>
     */
    public function execute(?int $categoryId = null): Collection
    {
        return Category::query()
            ->active()
            ->with([
                'products' => fn ($query) => $query
                    ->active()
                    ->with([
                        'featuredImage',
                        'options' => fn ($optionQuery) => $optionQuery
                            ->where('is_available', true)
                            ->where('price_baisa', '>', 0)
                            ->with([
                                'image',
                                'reservationItems' => fn ($reservationQuery) => $reservationQuery
                                    ->whereHas('reservation', fn ($query) => $query
                                        ->where('status', 'pending')
                                        ->where('expires_at', '>', now())),
                            ])
                            ->orderBy('sort_order')
                            ->orderBy('id'),
                    ])
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->each(function (Category $category): void {
                $category->setRelation(
                    'products',
                    $category->products
                        ->filter(fn (Product $product): bool => $product->options
                            ->filter(fn (ProductOption $option): bool => ! $option->tracks_inventory
                                || ($option->availableQuantity() ?? 0) > 0)
                            ->isNotEmpty())
                        ->values(),
                );
            })
            ->filter(fn (Category $category): bool => $category->products->isNotEmpty())
            ->values();
    }
}
