<?php

use App\Modules\Identity\Models\User;
use App\Modules\Store\Filament\Pages\ManageStoreSettings;
use App\Modules\Store\Filament\Resources\Categories\CategoryResource;
use App\Modules\Store\Filament\Resources\Options\ProductOptionResource;
use App\Modules\Store\Filament\Resources\Orders\OrderResource;
use App\Modules\Store\Filament\Resources\Orders\Pages\ViewOrder;
use App\Modules\Store\Filament\Resources\Products\ProductResource;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\OrderItem;
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
        ->and(ProductOptionResource::getNavigationLabel())->toBe('خيارات المنتجات')
        ->and(OrderResource::getNavigationLabel())->toBe('الطلبات')
        ->and(ManageStoreSettings::getNavigationLabel())->toBe('إعدادات المتجر')
        ->and(__('admin.fields.tracks_inventory'))->toBe('يتتبع المخزون')
        ->and(__('admin.store.order_statuses.ready_for_pickup'))->toBe('جاهز للاستلام');
});
