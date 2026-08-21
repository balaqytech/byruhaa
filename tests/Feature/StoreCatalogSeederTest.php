<?php

use App\Modules\Store\Enums\ProductStatus;
use App\Modules\Store\Models\Category;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use Database\Seeders\DatabaseSeeder;
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

    $skuToSlug = ProductOption::query()
        ->with('product:id,slug')
        ->get()
        ->mapWithKeys(fn (ProductOption $option): array => [$option->sku => $option->product->slug])
        ->all();

    $expectedSkuToSlug = [];
    $productSkuGroups = [
        'catalogue-1' => ['BYR-0001'],
        'catalogue-2' => ['BYR-0002'],
        'catalogue-3' => ['BYR-0003'],
        'catalogue-4' => ['BYR-0004'],
        'catalogue-5' => ['BYR-0005', 'BYR-0006', 'BYR-0007', 'BYR-0008', 'BYR-0009', 'BYR-0010'],
        'catalogue-11' => ['BYR-0011'],
        'catalogue-12' => ['BYR-0012', 'BYR-0013', 'BYR-0014', 'BYR-0015', 'BYR-0016', 'BYR-0017'],
        'catalogue-18' => ['BYR-0018', 'BYR-0019'],
        'catalogue-20' => ['BYR-0020'],
        'catalogue-21' => ['BYR-0021', 'BYR-0022', 'BYR-0023', 'BYR-0024', 'BYR-0025', 'BYR-0026'],
        'catalogue-27' => ['BYR-0027', 'BYR-0028', 'BYR-0029', 'BYR-0030', 'BYR-0031'],
        'catalogue-32' => ['BYR-0032', 'BYR-0033', 'BYR-0034'],
        'catalogue-35' => ['BYR-0035'],
        'catalogue-36' => ['BYR-0036'],
        'catalogue-37' => ['BYR-0037'],
        'catalogue-38' => ['BYR-0038'],
        'catalogue-39' => ['BYR-0039'],
        'catalogue-40' => ['BYR-0040'],
        'catalogue-41' => ['BYR-0041'],
        'catalogue-42' => ['BYR-0042'],
        'kark-byruha' => ['BYR-0043'],
        'afnaa-brew' => ['BYR-0044'],
        'naseem-qarnan' => ['BYR-0045'],
    ];

    foreach ($productSkuGroups as $slug => $skus) {
        foreach ($skus as $sku) {
            $expectedSkuToSlug[$sku] = $slug;
        }
    }

    expect($skuToSlug)
        ->toHaveCount(45)
        ->toMatchArray($expectedSkuToSlug);

    expect(Product::query()->withCount(['options as default_options_count' => fn ($query) => $query->where('is_default', true)])
        ->pluck('default_options_count')
        ->every(fn (int $count): bool => $count === 1))->toBeTrue();

    $this->seed(StoreCatalogSeeder::class);

    expect(Product::query()->count())->toBe(23)
        ->and(ProductOption::query()->count())->toBe(45);
});

test('database seeder can build the complete application seed path', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(Category::query()->count())->toBe(9)
        ->and(Product::query()->count())->toBe(23)
        ->and(ProductOption::query()->count())->toBe(45)
        ->and(ProductOption::query()->where('is_default', true)->count())->toBe(23);
});

test('rerunning the catalog seeder preserves panel-managed product data', function (): void {
    $this->seed(StoreCatalogSeeder::class);

    $product = Product::query()->where('slug', 'catalogue-5')->firstOrFail();
    $replacementCategory = Category::query()->where('slug', 'store')->firstOrFail();
    $option = $product->defaultOption()->firstOrFail();

    $product->update([
        'category_id' => $replacementCategory->id,
        'name' => 'Team curated latte',
        'status' => ProductStatus::Active,
        'is_featured' => false,
        'featured_sort_order' => 99,
    ]);
    $option->update([
        'price_baisa' => 9999,
        'stock_on_hand' => 12,
        'tracks_inventory' => true,
    ]);

    $this->seed(StoreCatalogSeeder::class);

    $product->refresh();
    $option->refresh();

    expect($product->category_id)->toBe($replacementCategory->id)
        ->and($product->name)->toBe('Team curated latte')
        ->and($product->status)->toBe(ProductStatus::Active)
        ->and($product->is_featured)->toBeFalse()
        ->and($product->featured_sort_order)->toBe(99)
        ->and($option->price_baisa)->toBe(9999)
        ->and($option->stock_on_hand)->toBe(12)
        ->and($option->tracks_inventory)->toBeTrue();
});
