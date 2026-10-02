<?php

use App\Modules\Identity\Models\User;
use App\Modules\Store\Filament\Resources\Orders\Pages\CreateOrder;
use App\Modules\Store\Filament\Resources\Orders\Pages\ListOrders;
use App\Modules\Store\Filament\Resources\Orders\Pages\ViewOrder;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\OrderItem;
use Livewire\Livewire;

test('order list tabs group orders by operational status', function (): void {
    $this->actingAs(User::factory()->create(), 'web');

    $pending = Order::factory()->create(['status' => 'pending_payment']);
    $confirmed = Order::factory()->create(['status' => 'confirmed']);
    $completed = Order::factory()->create(['status' => 'completed']);
    $refunded = Order::factory()->create(['status' => 'refunded']);

    Livewire::test(ListOrders::class)
        ->assertActionExists('create')
        ->set('activeTab', 'pending_payment')
        ->assertCanSeeTableRecords([$pending])
        ->assertCanNotSeeTableRecords([$confirmed, $completed, $refunded]);

    Livewire::test(ListOrders::class)
        ->set('activeTab', 'in_progress')
        ->assertCanSeeTableRecords([$confirmed])
        ->assertCanNotSeeTableRecords([$pending, $completed, $refunded]);

    Livewire::test(ListOrders::class)
        ->set('activeTab', 'exceptions')
        ->assertCanSeeTableRecords([$refunded])
        ->assertCanNotSeeTableRecords([$pending, $confirmed, $completed]);
});

test('order list can filter by payment and pickup method', function (): void {
    $this->actingAs(User::factory()->create(), 'web');

    $walletPickup = Order::factory()->create(['payment_method' => 'wallet', 'pickup_type' => 'scheduled']);
    $directPickup = Order::factory()->create(['payment_method' => 'thawani', 'pickup_type' => 'immediate']);

    Livewire::test(ListOrders::class)
        ->assertTableColumnExists('payment_method')
        ->filterTable('payment_method', 'wallet')
        ->assertCanSeeTableRecords([$walletPickup])
        ->assertCanNotSeeTableRecords([$directPickup]);

    Livewire::test(ListOrders::class)
        ->filterTable('pickup_type', 'scheduled')
        ->assertCanSeeTableRecords([$walletPickup])
        ->assertCanNotSeeTableRecords([$directPickup]);
});

test('admin order forms and details group information into clear tabs', function (): void {
    $this->actingAs(User::factory()->create(), 'web');
    $order = Order::factory()->create();
    OrderItem::factory()->for($order)->create(['note' => 'No sugar']);

    Livewire::test(CreateOrder::class)
        ->assertSuccessful()
        ->assertSee(__('admin.store.order_tabs.customer_payment'))
        ->assertSee(__('admin.store.sections.items'))
        ->assertSee(__('admin.store.order_tabs.pickup_notes'))
        ->assertFormFieldExists('customer_id')
        ->assertFormFieldExists('items');

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->assertSuccessful()
        ->assertSee(__('admin.store.order_tabs.customer_payment'))
        ->assertSee(__('admin.store.sections.status_history'))
        ->assertSee($order->reference)
        ->assertSee('TEST-SKU')
        ->assertSee('No sugar')
        ->assertSee(__('admin.fields.total_including_vat'));
});
