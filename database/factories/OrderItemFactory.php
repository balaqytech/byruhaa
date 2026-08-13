<?php

namespace Database\Factories;

use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrderItem> */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_option_id' => null,
            'product_name' => 'Test product',
            'option_name' => 'Standard',
            'sku' => 'TEST-SKU',
            'unit_price_baisa' => 1000,
            'quantity' => 1,
            'vat_baisa' => 50,
            'line_subtotal_baisa' => 1000,
            'line_total_baisa' => 1050,
            'note' => null,
        ];
    }
}
