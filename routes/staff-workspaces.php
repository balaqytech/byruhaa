<?php

use App\Modules\Identity\Http\Controllers\StaffWorkspaceAuthController;
use App\Modules\Pos\Http\Controllers\CashierOrderReceiptController;
use App\Modules\Pos\Livewire\BaristaBoard;
use App\Modules\Pos\Livewire\CashierOrders;
use App\Modules\Pos\Livewire\PickupBoard;
use App\Modules\Pos\Livewire\PosTerminal;
use Illuminate\Support\Facades\Route;

Route::prefix('cashier')->name('cashier.')->group(function (): void {
    Route::get('login', [StaffWorkspaceAuthController::class, 'cashierLoginPage'])->name('login');
    Route::post('login', [StaffWorkspaceAuthController::class, 'cashierLogin'])->middleware('throttle:login')->name('login.store');

    Route::middleware(['auth:cashier', 'can:Sell:Pos'])->group(function (): void {
        Route::get('/', PosTerminal::class)->name('terminal');
        Route::get('orders', CashierOrders::class)->name('orders');
        Route::get('orders/{order}/receipt', [CashierOrderReceiptController::class, 'show'])->name('orders.receipt');
        Route::post('logout', [StaffWorkspaceAuthController::class, 'cashierLogout'])->name('logout');
    });
});

Route::prefix('pickup')->name('pickup.')->group(function (): void {
    Route::get('login', [StaffWorkspaceAuthController::class, 'pickupLoginPage'])->name('login');
    Route::post('login', [StaffWorkspaceAuthController::class, 'pickupLogin'])->middleware('throttle:login')->name('login.store');

    Route::middleware(['auth:pickup', 'can:View:PickupBoard'])->group(function (): void {
        Route::get('/', PickupBoard::class)->name('orders');
        Route::post('logout', [StaffWorkspaceAuthController::class, 'pickupLogout'])->name('logout');
    });
});

Route::prefix('barista')->name('barista.')->group(function (): void {
    Route::get('login', [StaffWorkspaceAuthController::class, 'baristaLoginPage'])->name('login');
    Route::post('login', [StaffWorkspaceAuthController::class, 'baristaLogin'])->middleware('throttle:login')->name('login.store');

    Route::middleware(['auth:barista', 'can:View:BaristaBoard'])->group(function (): void {
        Route::get('/', BaristaBoard::class)->name('orders');
        Route::post('logout', [StaffWorkspaceAuthController::class, 'baristaLogout'])->name('logout');
    });
});
