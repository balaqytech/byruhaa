<?php

use App\Modules\Identity\Models\User;
use App\Modules\Store\Filament\Pages\ManageStoreSettings;
use App\Modules\Store\Filament\Resources\Categories\CategoryResource;
use App\Modules\Store\Filament\Resources\Orders\OrderResource;
use App\Modules\Store\Filament\Resources\Orders\Pages\ViewOrder;
use App\Modules\Store\Filament\Resources\Products\Pages\EditProduct;
use App\Modules\Store\Filament\Resources\Products\Pages\ListProducts;
use App\Modules\Store\Filament\Resources\Products\ProductResource;
use App\Modules\Store\Filament\Resources\Products\RelationManagers\InventoryMovementsRelationManager;
use App\Modules\Store\Filament\Resources\Products\RelationManagers\OptionsRelationManager;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\OrderItem;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use Livewire\Livewire;

test('staff can view store orders in filament', function (): void {
    $staff = User::factory()->create();
    $order = Order::factory()->create();
    OrderItem::factory()->for($order)->create();

    $this->actingAs($staff, 'web')
        ->get('/admin/orders/'.$order->getRouteKey())
        ->assertOk();

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->assertSuccessful();
});

test('store filament resources use Arabic translations', function (): void {
    app()->setLocale('ar');

    expect(CategoryResource::getNavigationLabel())->toBe('التصنيفات')
        ->and(ProductResource::getNavigationLabel())->toBe('المنتجات')
        ->and(OrderResource::getNavigationLabel())->toBe('الطلبات')
        ->and(ManageStoreSettings::getNavigationLabel())->toBe('إعدادات المتجر')
        ->and(__('admin.fields.tracks_inventory'))->toBe('يتتبع المخزون')
        ->and(__('admin.store.order_statuses.ready_for_pickup'))->toBe('جاهز للاستلام');
});

test('staff manage product options and inventory from the product page', function (): void {
    $this->actingAs(User::factory()->create(), 'web');

    $product = Product::factory()->create();
    $memberOption = $product->defaultOption()->firstOrFail();
    $memberOption->update(['price_baisa' => 1600, 'member_price_baisa' => 1100, 'tracks_inventory' => true]);
    $standardOption = ProductOption::factory()->for($product)->create([
        'price_baisa' => 1400,
        'member_price_baisa' => null,
    ]);

    Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
        ->assertFormFieldExists('options')
        ->assertSee(__('admin.store.product_tabs.details'))
        ->assertSee(__('admin.store.product_tabs.media'))
        ->assertSee(__('admin.store.product_tabs.options'));

    Livewire::test(OptionsRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
        ->assertTableColumnExists('member_price_baisa')
        ->assertCanSeeTableRecords([$memberOption, $standardOption])
        ->callTableAction('adjust-stock', $memberOption, ['quantity_change' => 5, 'reason' => 'Admin count']);

    expect($memberOption->fresh()->stock_on_hand)->toBe(5)
        ->and($memberOption->inventoryMovements()->count())->toBe(1);

    Livewire::test(InventoryMovementsRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
        ->assertCanSeeTableRecords([$memberOption->inventoryMovements()->firstOrFail()])
        ->assertSee('Admin count');

    $this->get('/admin/product-options')->assertNotFound();
});

test('staff can filter the product list with status tabs', function (): void {
    $this->actingAs(User::factory()->create(), 'web');
    $active = Product::factory()->active()->create();
    $draft = Product::factory()->create();

    Livewire::test(ListProducts::class)
        ->assertTableColumnExists('starting_price')
        ->assertTableColumnExists('options_count')
        ->set('activeTab', 'active')
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$draft]);
});
