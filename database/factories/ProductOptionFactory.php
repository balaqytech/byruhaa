<?php

namespace Database\Factories;

use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ProductOption> */
class ProductOptionFactory extends Factory
{
    protected $model = ProductOption::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => 'Standard',
            'sku' => 'SKU-'.Str::upper(Str::random(10)),
            'price_baisa' => fake()->numberBetween(500, 5000),
            'currency' => 'OMR',
            'image_id' => null,
            'sort_order' => 0,
            'is_available' => true,
            'is_default' => null,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
        ]);
    }

    public function unavailable(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_available' => false,
        ]);
    }
}
