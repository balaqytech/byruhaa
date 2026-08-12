<?php

namespace Database\Factories;

use App\Modules\Store\Enums\ProductStatus;
use App\Modules\Store\Models\Category;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);
        $name = is_array($name) ? implode(' ', $name) : $name;

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'description' => fake()->optional()->paragraph(),
            'featured_image_id' => null,
            'status' => ProductStatus::Draft,
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Product $product): void {
            if (! $product->options()->exists()) {
                ProductOption::factory()->for($product)->default()->create();
            }
        });
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProductStatus::Active,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProductStatus::Archived,
        ]);
    }
}
