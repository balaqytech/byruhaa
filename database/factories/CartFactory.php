<?php

namespace Database\Factories;

use App\Modules\Store\Models\Cart;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Cart> */
class CartFactory extends Factory
{
    protected $model = Cart::class;

    public function definition(): array
    {
        return ['customer_id' => null];
    }
}
