<?php

use App\Http\Controllers\Api\V1\AssistantEventInterestController;
use App\Http\Controllers\Api\V1\CustomerBookingController;
use App\Http\Controllers\Api\V1\CustomerBookingPaymentController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\CustomerFamilyMemberController;
use App\Http\Controllers\Api\V1\CustomerPaymentController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\ThawaniPaymentWebhookController;
use App\Http\Controllers\UchatAccountController;
use App\Http\Middleware\AuthenticateUchatStore;
use App\Http\Middleware\LocalizeUchatResponse;
use App\Modules\Store\Http\Controllers\UchatStoreController;
use Illuminate\Support\Facades\Route;

Route::post('webhooks/thawani', ThawaniPaymentWebhookController::class)
    ->name('api.webhooks.thawani');

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function (): void {
        Route::middleware(['throttle:uchat-store', AuthenticateUchatStore::class])->group(function (): void {
            Route::patch('customers/{customer}/profile', [CustomerController::class, 'updateProfile'])->name('customers.profile.update');
            Route::apiResource('customers', CustomerController::class);

            Route::scopeBindings()->group(function (): void {
                Route::apiResource('customers.bookings', CustomerBookingController::class)->only(['index', 'store', 'show']);
                Route::post('customers/{customer}/bookings/{booking}/payments', [CustomerBookingPaymentController::class, 'store'])
                    ->name('customers.bookings.payments.store');
                Route::apiResource('customers.family-members', CustomerFamilyMemberController::class);
            });

            Route::apiResource('customers.payments', CustomerPaymentController::class);
        });
        Route::apiResource('events', EventController::class)->only(['index', 'show']);

        Route::middleware(['throttle:20,1', AuthenticateUchatStore::class])
            ->prefix('integrations/assistant')
            ->name('integrations.assistant.')
            ->group(function (): void {
                Route::get('event-interests', [AssistantEventInterestController::class, 'show'])->name('event-interests.show');
                Route::put('event-interests', [AssistantEventInterestController::class, 'update'])->name('event-interests.update');
                Route::delete('event-interests', [AssistantEventInterestController::class, 'destroy'])->name('event-interests.destroy');
            });

        Route::middleware([LocalizeUchatResponse::class, 'throttle:uchat-store', AuthenticateUchatStore::class])
            ->prefix('integrations/uchat/store')
            ->name('integrations.uchat.store.')
            ->group(function (): void {
                Route::get('account', [UchatAccountController::class, 'account'])->name('account');
                Route::get('minor-profiles', [UchatAccountController::class, 'index'])->name('minor-profiles.index');
                Route::post('minor-profiles', [UchatAccountController::class, 'store'])->middleware('throttle:10,1')->name('minor-profiles.store');
                Route::post('minor-profiles/{minorProfile}/activation-link', [UchatAccountController::class, 'activationLink'])->whereNumber('minorProfile')->middleware('throttle:5,1')->name('minor-profiles.activation-link');
                Route::post('minor-profiles/{minorProfile}/verification/send', [UchatAccountController::class, 'resend'])->whereNumber('minorProfile')->middleware('throttle:5,1')->name('minor-profiles.resend');
                Route::post('minor-profiles/{minorProfile}/verification/verify', [UchatAccountController::class, 'verify'])->whereNumber('minorProfile')->middleware('throttle:10,1')->name('minor-profiles.verify');
                Route::post('phone-verification/send', [UchatAccountController::class, 'sendPhoneCode'])->middleware('throttle:5,1')->name('phone-verification.send');
                Route::post('phone-verification/verify', [UchatAccountController::class, 'verifyPhone'])->middleware('throttle:10,1')->name('phone-verification.verify');
                Route::get('catalog', [UchatStoreController::class, 'catalog'])->name('catalog');
                Route::get('products/{slug}', [UchatStoreController::class, 'product'])->name('products.show');
                Route::get('cart', [UchatStoreController::class, 'cart'])->name('cart.show');
                Route::post('cart/items', [UchatStoreController::class, 'addCartItem'])->name('cart.items.store');
                Route::patch('cart/items/{sku}', [UchatStoreController::class, 'updateCartItem'])->name('cart.items.update');
                Route::delete('cart/items/{sku}', [UchatStoreController::class, 'removeCartItem'])->name('cart.items.destroy');
                Route::post('orders', [UchatStoreController::class, 'createOrder'])->name('orders.store');
                Route::post('orders/{reference}/payment', [UchatStoreController::class, 'initiatePayment'])->name('orders.payment.store');
                Route::get('wallet', [UchatStoreController::class, 'wallet'])->name('wallet.show');
                Route::get('wallet/movements', [UchatStoreController::class, 'walletMovements'])->name('wallet.movements');
                Route::get('wallet/top-ups/{reference}', [UchatStoreController::class, 'walletTopUpStatus'])->name('wallet.top-ups.show');
                Route::post('wallet/top-ups', [UchatStoreController::class, 'walletTopUp'])->name('wallet.top-ups.store');
                Route::get('orders', [UchatStoreController::class, 'orders'])->name('orders.index');
                Route::get('orders/{reference}', [UchatStoreController::class, 'order'])->name('orders.show');
            });
    });
