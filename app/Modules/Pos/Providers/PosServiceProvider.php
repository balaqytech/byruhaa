<?php

namespace App\Modules\Pos\Providers;

use App\Modules\Pos\Livewire\BaristaBoard;
use App\Modules\Pos\Livewire\CashierOrders;
use App\Modules\Pos\Livewire\PickupBoard;
use App\Modules\Pos\Livewire\PosTerminal;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class PosServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Livewire::addComponent(name: 'store.pos-terminal', class: PosTerminal::class);
        Livewire::addComponent(name: 'store.barista-board', class: BaristaBoard::class);
        Livewire::addComponent(name: 'store.cashier-orders', class: CashierOrders::class);
        Livewire::addComponent(name: 'store.pickup-board', class: PickupBoard::class);
    }
}
