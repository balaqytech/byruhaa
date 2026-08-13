<?php

use App\Modules\Store\Enums\ProductStatus;
use App\Modules\Store\Models\Category;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use Database\Seeders\StoreCatalogSeeder;

test('store catalogue seeder imports the 42 source products as drafts with deterministic skus', function (): void {
    $this->seed(StoreCatalogSeeder::class);

    expect(Category::query()->count())->toBe(7)
        ->and(Product::query()->count())->toBe(42)
        ->and(ProductOption::query()->count())->toBe(42)
        ->and(Product::query()->where('status', ProductStatus::Draft)->count())->toBe(42)
        ->and(ProductOption::query()->where('sku', 'BYR-0001')->exists())->toBeTrue()
        ->and(ProductOption::query()->where('sku', 'BYR-0042')->exists())->toBeTrue();

    $this->seed(StoreCatalogSeeder::class);

    expect(Product::query()->count())->toBe(42)
        ->and(ProductOption::query()->count())->toBe(42);
});
