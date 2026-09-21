<?php

use App\Livewire\Store\CoffeeStore;
use App\Modules\Store\Enums\ProductStatus;
use App\Modules\Store\Models\Category;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\StoreCatalogSeeder;
use Livewire\Livewire;

test('store catalogue seeder imports the confirmed active catalogue with deterministic skus', function (): void {
    $this->seed(StoreCatalogSeeder::class);

    expect(Category::query()->count())->toBe(8)
        ->and(Product::query()->count())->toBe(74)
        ->and(ProductOption::query()->count())->toBe(79)
        ->and(Product::query()->where('status', ProductStatus::Active)->count())->toBe(74)
        ->and(ProductOption::query()->where('sku', 'BYR-0001')->exists())->toBeTrue()
        ->and(ProductOption::query()->where('sku', 'BYR-0079')->exists())->toBeTrue()
        ->and(Product::query()->where('is_featured', true)->count())->toBe(0);

    expect(Category::query()->orderBy('sort_order')->pluck('slug')->all())->toBe([
        'fresh',
        'frozen',
        'sweets',
        'cold',
        'hot',
        'tools',
        'antiques',
        'books',
    ]);

    $karkadeh = Product::query()->where('slug', 'fresh-09')->firstOrFail();

    expect($karkadeh->options()->count())->toBe(6)
        ->and($karkadeh->defaultOption()->value('name'))->toBe('بلا إضافة')
        ->and($karkadeh->options()->pluck('price_baisa')->all())->toBe([1600, 1800, 1800, 1800, 1800, 1800])
        ->and(Product::query()->whereNotNull('source_name')->count())->toBe(57)
        ->and(Product::query()->whereNotNull('author_name')->count())->toBe(9)
        ->and(Product::query()->whereJsonLength('allergens', '>', 0)->count())->toBe(28);

    expect(Product::query()
        ->withCount(['options as default_options_count' => fn ($query) => $query->where('is_default', true)])
        ->pluck('default_options_count')
        ->every(fn (int $count): bool => $count === 1))->toBeTrue();

    $this->seed(StoreCatalogSeeder::class);

    expect(Category::query()->count())->toBe(8)
        ->and(Product::query()->count())->toBe(74)
        ->and(ProductOption::query()->count())->toBe(79);
});

test('database seeder can build the complete application seed path', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(Category::query()->count())->toBe(8)
        ->and(Product::query()->count())->toBe(74)
        ->and(ProductOption::query()->count())->toBe(79)
        ->and(ProductOption::query()->where('is_default', true)->count())->toBe(74);
});

test('confirmed catalogue renders as a category card carousel with one department at a time', function (): void {
    $this->seed(StoreCatalogSeeder::class);

    $fresh = Category::query()->where('slug', 'fresh')->firstOrFail();
    $frozen = Category::query()->where('slug', 'frozen')->firstOrFail();

    Livewire::test(CoffeeStore::class)
        ->assertSet('categoryId', $fresh->id)
        ->assertSee('snap-mandatory', false)
        ->assertSee('الموهيتو')
        ->assertSee('9 صنفًا')
        ->assertSee('المنعش الفوّار بالباشن')
        ->assertDontSee('مثلّجة القهوة')
        ->call('selectCategory', $frozen->id)
        ->assertSet('categoryId', $frozen->id)
        ->assertSee('مثلّجة القهوة')
        ->assertDontSee('المنعش الفوّار بالباشن');
});

test('rerunning the catalog seeder preserves panel-managed product data', function (): void {
    $this->seed(StoreCatalogSeeder::class);

    $product = Product::query()->where('slug', 'fresh-01')->firstOrFail();
    $replacementCategory = Category::query()->where('slug', 'books')->firstOrFail();
    $option = $product->defaultOption()->firstOrFail();

    $product->update([
        'category_id' => $replacementCategory->id,
        'name' => 'Team curated refresher',
        'status' => ProductStatus::Archived,
        'is_featured' => true,
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
        ->and($product->name)->toBe('Team curated refresher')
        ->and($product->status)->toBe(ProductStatus::Archived)
        ->and($product->is_featured)->toBeTrue()
        ->and($product->featured_sort_order)->toBe(99)
        ->and($option->price_baisa)->toBe(9999)
        ->and($option->stock_on_hand)->toBe(12)
        ->and($option->tracks_inventory)->toBeTrue();
});
