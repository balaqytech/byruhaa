<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Models\ProductOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SetDefaultProductOption
{
    public function execute(ProductOption $option): ProductOption
    {
        return DB::transaction(function () use ($option): ProductOption {
            $option = ProductOption::query()->whereKey($option->getKey())->lockForUpdate()->firstOrFail();

            if (! $option->product_id) {
                throw ValidationException::withMessages(['product' => 'A product option must belong to a product.']);
            }

            $option->forceFill(['is_default' => true])->save();

            return $option->refresh();
        });
    }
}
