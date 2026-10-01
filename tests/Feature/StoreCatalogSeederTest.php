<?php

use App\Livewire\Store\CoffeeStore;
use App\Livewire\Store\ProductDetail;
use App\Modules\Store\Actions\BrowseProduct;
use App\Modules\Store\Enums\ProductStatus;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\Category;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use App\Modules\Store\Settings\StoreSettings;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\StoreCatalogSeeder;
use Livewire\Livewire;

test('store catalogue seeder retains the previously active products and prices', function (): void {
    $this->seed(StoreCatalogSeeder::class);

    expect(Category::query()->count())->toBe(8)
        ->and(Product::query()->count())->toBe(145)
        ->and(ProductOption::query()->count())->toBe(150)
        ->and(Product::query()->where('status', ProductStatus::Active)->count())->toBe(74)
        ->and(ProductOption::query()->where('sku', 'FR-001')->exists())->toBeTrue()
        ->and(ProductOption::query()->where('sku', 'BK-022')->exists())->toBeTrue()
        ->and(ProductOption::query()->where('sku', 'PR-030')->exists())->toBeTrue()
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
        ->and(Product::query()->whereNotNull('long_description')->count())->toBe(80)
        ->and(Product::query()->where('slug', 'proposal-001')->firstOrFail()->status)->toBe(ProductStatus::Draft)
        ->and(Product::query()->where('slug', 'sweets-01')->firstOrFail()->status)->toBe(ProductStatus::Active);

    expect(ProductOption::query()->where('sku', 'FR-006')->value('member_price_baisa'))->toBe(1120)
        ->and(ProductOption::query()->where('sku', 'FR-001')->value('member_price_baisa'))->toBeNull()
        ->and(ProductOption::query()->where('sku', 'SW-001')->value('price_baisa'))->toBe(1700)
        ->and(ProductOption::query()->where('sku', 'SW-002')->value('price_baisa'))->toBe(1500)
        ->and(ProductOption::query()->where('sku', 'BK-005')->value('price_baisa'))->toBe(4000)
        ->and(ProductOption::query()->where('sku', 'BYR-0016')->value('price_baisa'))->toBe(1800)
        ->and(ProductOption::query()->where('sku', 'BYR-0051')->value('price_baisa'))->toBe(8500)
        ->and(ProductOption::query()->where('sku', 'BYR-0060')->value('price_baisa'))->toBe(4000)
        ->and(ProductOption::query()->where('sku', 'BYR-0079')->value('price_baisa'))->toBe(4500)
        ->and(Product::query()->where('slug', 'legacy-books-12')->firstOrFail()->long_description)->toBeNull();

    expect(Category::query()->withCount(['products as active_products_count' => fn ($query) => $query->where('status', ProductStatus::Active)])
        ->orderBy('sort_order')
        ->pluck('active_products_count')->all())->toBe([9, 4, 5, 12, 14, 10, 8, 12]);

    expect(Product::query()
        ->withCount(['options as default_options_count' => fn ($query) => $query->where('is_default', true)])
        ->pluck('default_options_count')
        ->every(fn (int $count): bool => $count === 1))->toBeTrue();

    $this->seed(StoreCatalogSeeder::class);

    expect(Category::query()->count())->toBe(8)
        ->and(Product::query()->count())->toBe(145)
        ->and(ProductOption::query()->count())->toBe(150);
});

test('database seeder can build the complete application seed path', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(Category::query()->count())->toBe(8)
        ->and(Product::query()->count())->toBe(145)
        ->and(ProductOption::query()->count())->toBe(150)
        ->and(ProductOption::query()->where('is_default', true)->count())->toBe(145);
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
        ->assertDontSee('الجميدة')
        ->call('selectCategory', $frozen->id)
        ->assertSet('categoryId', $frozen->id)
        ->assertSee('الجميدة')
        ->assertSee('4 صنفًا')
        ->assertSee('مثلّجة القهوة')
        ->assertDontSee('المنعش الفوّار بالباشن');
});

test('product page renders formatted long description and never exposes draft proposals', function (): void {
    $this->seed(StoreCatalogSeeder::class);

    $product = Product::query()->where('slug', 'fresh-01')->firstOrFail();
    $proposal = Product::query()->where('slug', 'proposal-001')->firstOrFail();
    $iceCream = Product::query()->where('slug', 'frozen-01')->firstOrFail();
    $karkadeh = Product::query()->where('slug', 'fresh-09')->firstOrFail();

    $this->get(route('coffee.product', ['slug' => $product->slug]))
        ->assertSuccessful()
        ->assertSee('product-title', false)
        ->assertSee('للباشن حموضةٌ عطرية')
        ->assertSee('<h3>للفتى</h3>', false);

    $this->get(route('coffee.product', ['slug' => $iceCream->slug]))
        ->assertSee('مسبّبات الحساسية')
        ->assertSee('حليب')
        ->assertSee('الطلب متوقف مؤقتًا');

    $this->get(route('coffee.product', ['slug' => $karkadeh->slug]))
        ->assertSee('اختر الخيار')
        ->assertSee('كركديه');

    $this->get(route('coffee.product', ['slug' => $proposal->slug]))->assertNotFound();
});

test('product page can add the selected product option to the cart', function (): void {
    $this->seed(StoreCatalogSeeder::class);

    $settings = app(StoreSettings::class);
    $settings->ordering_enabled = true;
    $settings->save();

    $product = Product::query()->where('slug', 'fresh-09')->firstOrFail();
    $berryOption = $product->options()->where('sku', 'FR-009-01')->firstOrFail();

    Livewire::test(ProductDetail::class, ['product' => app(BrowseProduct::class)->execute($product->slug)])
        ->set('selectedOptionId', $berryOption->id)
        ->assertSee($berryOption->name)
        ->call('addToCart')
        ->assertSet('feedback', 'أضيف المنتج إلى السلة.');

    expect(Cart::query()->firstOrFail()->items()->firstOrFail()->product_option_id)->toBe($berryOption->id);
});

test('rerunning the catalog seeder refreshes source content while preserving merchandising and stock', function (): void {
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

    expect($product->category_id)->not->toBe($replacementCategory->id)
        ->and($product->name)->toBe('المنعش الفوّار بالباشن')
        ->and($product->status)->toBe(ProductStatus::Active)
        ->and($product->is_featured)->toBeTrue()
        ->and($product->featured_sort_order)->toBe(99)
        ->and($option->price_baisa)->toBe(1200)
        ->and($option->stock_on_hand)->toBe(12)
        ->and($option->tracks_inventory)->toBeTrue();
});
