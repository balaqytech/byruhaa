<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnsureDefaultProductOption
{
    public function execute(Product $product): ProductOption
    {
        return DB::transaction(function () use ($product): ProductOption {
            $options = ProductOption::query()->where('product_id', $product->getKey())->orderBy('id')->lockForUpdate()->get();

            if ($options->isEmpty()) {
                throw ValidationException::withMessages(['options' => 'A product must have at least one option.']);
            }

            $default = $options->first(fn (ProductOption $option): bool => $option->is_default === true) ?? $options->first();
            ProductOption::query()
                ->where('product_id', $product->getKey())
                ->whereKeyNot($default->getKey())
                ->update(['is_default' => null]);
            ProductOption::query()->whereKey($default->getKey())->update(['is_default' => true]);

            return $default->refresh();
        });
    }
}
