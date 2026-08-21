<?php

use App\Modules\Store\Enums\ProductStatus;
use App\Modules\Store\Models\Category;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use Database\Seeders\StoreCatalogSeeder;

test('store catalogue seeder imports the approved catalogue as drafts with deterministic skus', function (): void {
    $this->seed(StoreCatalogSeeder::class);

    expect(Category::query()->count())->toBe(9)
        ->and(Product::query()->count())->toBe(23)
        ->and(ProductOption::query()->count())->toBe(45)
        ->and(Product::query()->where('status', ProductStatus::Draft)->count())->toBe(23)
        ->and(ProductOption::query()->where('sku', 'BYR-0001')->exists())->toBeTrue()
        ->and(ProductOption::query()->where('sku', 'BYR-0045')->exists())->toBeTrue()
        ->and(Product::query()->where('is_featured', true)->count())->toBe(6);

    $this->seed(StoreCatalogSeeder::class);

    expect(Product::query()->count())->toBe(23)
        ->and(ProductOption::query()->count())->toBe(45);
});
