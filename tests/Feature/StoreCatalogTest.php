<?php

use App\Livewire\Store\CoffeeStore;
use App\Livewire\Store\FloatingCart;
use App\Modules\Store\Enums\ProductStatus;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\Category;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Slimani\MediaManager\Models\File;

test('catalog migrations create the store tables and required columns', function (): void {
    expect(Schema::hasTable('store_categories'))->toBeTrue()
        ->and(Schema::hasTable('store_products'))->toBeTrue()
        ->and(Schema::hasTable('store_product_options'))->toBeTrue();

    expect(Schema::getColumnListing('store_categories'))
        ->toContain('name')
        ->toContain('slug')
        ->toContain('is_active');

    expect(Schema::getColumnListing('store_products'))
        ->toContain('category_id')
        ->toContain('featured_image_id')
        ->toContain('status');

    expect(Schema::getColumnListing('store_product_options'))
        ->toContain('sku')
        ->toContain('price_baisa')
        ->toContain('image_id')
        ->toContain('is_default');
});

test('a product always starts with one default option', function (): void {
    $product = Product::factory()->create();

    expect($product->options()->count())->toBe(1)
        ->and($product->defaultOption()->count())->toBe(1)
        ->and($product->defaultOption()->first()->price->getMinorAmount()->toInt())->toBe(0);
});

test('saving a new default option replaces the previous default', function (): void {
    $product = Product::factory()->create();
    $previousDefault = $product->defaultOption()->firstOrFail();
    $newDefault = ProductOption::factory()
        ->for($product)
        ->default()
        ->create(['price_baisa' => 1250]);

    expect($product->fresh()->defaultOption()->value('id'))->toBe($newDefault->id)
        ->and($previousDefault->fresh()->is_default)->toBeNull()
        ->and($product->options()->where('is_default', true)->count())->toBe(1)
        ->and($newDefault->price->getMinorAmount()->toInt())->toBe(1250);
});

test('a product may have multiple available and unavailable non-default options', function (): void {
    $product = Product::factory()->create();
    $available = ProductOption::factory()->for($product)->create(['is_available' => true]);
    $unavailable = ProductOption::factory()->for($product)->unavailable()->create();

    expect($product->options()->where('is_default', true)->count())->toBe(1)
        ->and($available->fresh()->is_default)->toBeNull()
        ->and($unavailable->fresh()->is_available)->toBeFalse();
});

test('the default option cannot be demoted or deleted', function (): void {
    $option = Product::factory()->create()->defaultOption()->firstOrFail();

    expect(fn (): bool => $option->update(['is_default' => false]))
        ->toThrow(ValidationException::class);

    $option->refresh();

    expect(fn (): bool => (bool) $option->delete())
        ->toThrow(ValidationException::class);
});

test('product option SKUs are unique and prices are stored in baisa', function (): void {
    $product = Product::factory()->create();
    $default = $product->defaultOption()->firstOrFail();

    expect(fn (): ProductOption => ProductOption::factory()
        ->for($product)
        ->create(['sku' => $default->sku]))
        ->toThrow(QueryException::class);

    expect($default->price->getMinorAmount()->toInt())->toBe(0)
        ->and($default->currency)->toBe('OMR');
});

test('catalog status and activation scopes expose only active records', function (): void {
    $activeCategory = Category::factory()->create();
    $inactiveCategory = Category::factory()->inactive()->create();
    $activeProduct = Product::factory()->active()->create(['category_id' => $activeCategory->id]);
    $archivedProduct = Product::factory()->archived()->create(['category_id' => $activeCategory->id]);

    expect(Category::active()->pluck('id')->all())
        ->toContain($activeCategory->id)
        ->not->toContain($inactiveCategory->id);

    expect(Product::active()->pluck('id')->all())
        ->toContain($activeProduct->id)
        ->not->toContain($archivedProduct->id)
        ->and($activeProduct->status)->toBe(ProductStatus::Active);
});

test('coffee store keeps all category tabs visible when filtering products', function (): void {
    $manualCategory = Category::factory()->create(['name' => 'Manual brewing']);
    $espressoCategory = Category::factory()->create(['name' => 'Espresso']);

    $manualProduct = Product::factory()->active()->create([
        'category_id' => $manualCategory->id,
        'name' => 'V60 Coffee',
    ]);
    $manualProduct->defaultOption()->update(['price_baisa' => 1500]);

    $espressoProduct = Product::factory()->active()->create([
        'category_id' => $espressoCategory->id,
        'name' => 'Espresso Coffee',
    ]);
    $espressoProduct->defaultOption()->update(['price_baisa' => 1800]);

    Livewire::test(CoffeeStore::class)
        ->assertSee($manualCategory->name)
        ->assertSee($espressoCategory->name)
        ->call('selectCategory', $manualCategory->id)
        ->assertSee($manualCategory->name)
        ->assertSee($espressoCategory->name)
        ->assertSee($manualProduct->name)
        ->assertDontSee($espressoProduct->name);
});

test('the floating cart is available globally after adding an item', function (): void {
    $category = Category::factory()->create();
    $product = Product::factory()->active()->create(['category_id' => $category->id]);
    $option = $product->defaultOption()->firstOrFail();
    $option->update(['price_baisa' => 1500]);

    Livewire::test(CoffeeStore::class)
        ->call('addToCart', $option->id)
        ->assertDispatched('store-cart-updated');

    $item = Cart::query()->where('token', session('store_cart_token'))->firstOrFail()->items()->firstOrFail();

    Livewire::test(FloatingCart::class)
        ->assertSee('عرض السلة')
        ->assertSee('1')
        ->call('openCart')
        ->assertSee('store-cart-drawer', false)
        ->assertSee('/store/checkout', false);

    $this->get(route('coffee'))
        ->assertSuccessful()
        ->assertSee('عرض السلة');

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('عرض السلة');

    Livewire::test(FloatingCart::class)
        ->call('updateItem', $item->id, 2)
        ->assertSee('2')
        ->call('removeItem', $item->id)
        ->assertSet('itemCount', 0)
        ->assertDontSee('عرض السلة');

    Livewire::test(CoffeeStore::class)->assertDontSee('id="cart-title"', false);
});

test('catalog records can be edited and archived without deletion', function (): void {
    $category = Category::factory()->create();
    $product = Product::factory()->active()->create(['category_id' => $category->id]);
    $option = $product->defaultOption()->firstOrFail();

    $category->update(['name' => 'Updated category', 'is_active' => false]);
    $product->update(['status' => ProductStatus::Archived]);
    $option->update(['is_available' => false]);

    expect($category->fresh())
        ->name->toBe('Updated category')
        ->is_active->toBeFalse()
        ->and($product->fresh()->status)->toBe(ProductStatus::Archived)
        ->and($option->fresh()->is_available)->toBeFalse()
        ->and(Product::query()->whereKey($product)->exists())->toBeTrue();
});

test('catalog image relationships use the existing media file model', function (): void {
    $product = Product::factory()->create();
    $option = $product->defaultOption()->firstOrFail();

    expect($product->featuredImage())->toBeInstanceOf(BelongsTo::class)
        ->and($option->image())->toBeInstanceOf(BelongsTo::class)
        ->and($product->featuredImage()->getRelated())->toBeInstanceOf(File::class)
        ->and($option->image()->getRelated())->toBeInstanceOf(File::class);
});

test('store Filament resources expose create and edit routes without delete actions', function (): void {
    $routeNames = collect(Route::getRoutes()->getRoutes())
        ->map(fn (Illuminate\Routing\Route $route): ?string => $route->getName())
        ->filter()
        ->values();

    expect($routeNames)
        ->toContain('filament.admin.resources.categories.index')
        ->toContain('filament.admin.resources.categories.create')
        ->toContain('filament.admin.resources.categories.edit')
        ->toContain('filament.admin.resources.products.index')
        ->toContain('filament.admin.resources.products.create')
        ->toContain('filament.admin.resources.products.edit')
        ->toContain('filament.admin.resources.options.product-options.index')
        ->toContain('filament.admin.resources.options.product-options.create')
        ->toContain('filament.admin.resources.options.product-options.edit');

    expect($routeNames->filter(fn (string $name): bool => str_contains($name, 'filament.admin.resources.categories.delete')
        || str_contains($name, 'filament.admin.resources.products.delete')
        || str_contains($name, 'filament.admin.resources.options.product-options.delete'))->all())
        ->toBeEmpty();
});
