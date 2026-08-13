<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Enums\ProductStatus;
use App\Modules\Store\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublishProduct
{
    public function execute(Product $product): Product
    {
        return DB::transaction(function () use ($product): Product {
            $product->refresh()->load(['category', 'options']);

            if (! $product->isPublishable()) {
                throw ValidationException::withMessages(['status' => 'The product needs an active category, one default option, a SKU, and a positive price before publishing.']);
            }

            $product->forceFill(['status' => ProductStatus::Active])->save();

            return $product->refresh();
        });
    }
}
