<?php

use App\Http\Controllers\Api\V1\AssistantEventInterestController;
use App\Http\Controllers\Api\V1\CustomerBookingController;
use App\Http\Controllers\Api\V1\CustomerBookingPaymentController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\CustomerFamilyMemberController;
use App\Http\Controllers\Api\V1\CustomerPaymentController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\ThawaniPaymentWebhookController;
use App\Http\Middleware\AuthenticateUchatStore;
use App\Modules\Store\Http\Controllers\UchatStoreController;
use Illuminate\Support\Facades\Route;

Route::post('webhooks/thawani', ThawaniPaymentWebhookController::class)
    ->name('api.webhooks.thawani');

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function (): void {
        Route::patch('customers/{customer}/profile', [CustomerController::class, 'updateProfile'])->name('customers.profile.update');
        Route::apiResource('customers', CustomerController::class);

        Route::scopeBindings()->group(function (): void {
            Route::apiResource('customers.bookings', CustomerBookingController::class)->only(['index', 'store', 'show']);
            Route::post('customers/{customer}/bookings/{booking}/payments', [CustomerBookingPaymentController::class, 'store'])
                ->name('customers.bookings.payments.store');
            Route::apiResource('customers.family-members', CustomerFamilyMemberController::class);
        });

        Route::apiResource('customers.payments', CustomerPaymentController::class);
        Route::apiResource('events', EventController::class)->only(['index', 'show']);

        Route::middleware('throttle:20,1')
            ->prefix('integrations/assistant')
            ->name('integrations.assistant.')
            ->group(function (): void {
                Route::get('event-interests', [AssistantEventInterestController::class, 'show'])->name('event-interests.show');
                Route::put('event-interests', [AssistantEventInterestController::class, 'update'])->name('event-interests.update');
                Route::delete('event-interests', [AssistantEventInterestController::class, 'destroy'])->name('event-interests.destroy');
            });

        Route::middleware(['throttle:uchat-store', AuthenticateUchatStore::class])
            ->prefix('integrations/uchat/store')
            ->name('integrations.uchat.store.')
            ->group(function (): void {
                Route::get('catalog', [UchatStoreController::class, 'catalog'])->name('catalog');
                Route::get('products/{slug}', [UchatStoreController::class, 'product'])->name('products.show');
                Route::get('cart', [UchatStoreController::class, 'cart'])->name('cart.show');
                Route::post('cart/items', [UchatStoreController::class, 'addCartItem'])->name('cart.items.store');
                Route::patch('cart/items/{sku}', [UchatStoreController::class, 'updateCartItem'])->name('cart.items.update');
                Route::delete('cart/items/{sku}', [UchatStoreController::class, 'removeCartItem'])->name('cart.items.destroy');
                Route::post('orders', [UchatStoreController::class, 'createOrder'])->name('orders.store');
                Route::post('orders/{reference}/payment', [UchatStoreController::class, 'initiatePayment'])->name('orders.payment.store');
                Route::get('orders', [UchatStoreController::class, 'orders'])->name('orders.index');
                Route::get('orders/{reference}', [UchatStoreController::class, 'order'])->name('orders.show');
            });
    });
