<?php

namespace Database\Factories;

use App\Modules\Store\Models\Order;
use App\Modules\Store\States\Order\PendingPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'idempotency_key' => fake()->unique()->uuid(),
            'status' => PendingPayment::$name,
            'customer_name' => fake()->name(),
            'customer_phone' => '+968'.fake()->numerify('9#######'),
            'customer_email' => fake()->safeEmail(),
            'subtotal_baisa' => 1000,
            'vat_baisa' => 50,
            'total_baisa' => 1050,
        ];
    }
}
