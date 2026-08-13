<?php

use App\Http\Controllers\Store\CartController;
use App\Http\Controllers\Store\OrderController;
use App\Http\Controllers\ThawaniPaymentReturnController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/public.php';
require __DIR__.'/affiliate.php';

Route::middleware('signed')->group(function () {
    Route::get('payments/thawani/{payment}/success', [ThawaniPaymentReturnController::class, 'success'])->name('payments.thawani.success');
    Route::get('payments/thawani/{payment}/cancel', [ThawaniPaymentReturnController::class, 'cancel'])->name('payments.thawani.cancel');
});

require __DIR__.'/customer.php';

Route::prefix('store')->name('store.')->middleware('throttle:60,1')->group(function (): void {
    Route::get('cart', [CartController::class, 'show'])->name('cart.show');
    Route::post('cart/items', [CartController::class, 'add'])->name('cart.items.store');
    Route::scopeBindings()->patch('cart/{cart:token}/items/{item}', [CartController::class, 'update'])->name('cart.items.update');
    Route::scopeBindings()->delete('cart/{cart:token}/items/{item}', [CartController::class, 'remove'])->name('cart.items.destroy');
    Route::post('orders', [OrderController::class, 'store'])->name('orders.store');
});
