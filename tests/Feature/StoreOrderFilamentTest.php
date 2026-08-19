<?php

use App\Modules\Identity\Models\User;
use App\Modules\Store\Filament\Resources\Orders\Pages\ViewOrder;
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
