<?php

namespace Database\Factories;

use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\CartItem;
use App\Modules\Store\Models\ProductOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CartItem> */
class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    public function definition(): array
    {
        return [
            'cart_id' => Cart::factory(),
            'product_option_id' => ProductOption::factory(),
            'quantity' => 1,
            'note' => null,
        ];
    }
}
