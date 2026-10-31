<?php

use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\ThawaniPaymentWebhookController;
use App\Http\Middleware\AuthenticateUchat;
use App\Http\Middleware\LocalizeUchatResponse;
use App\Http\Middleware\ResolveUchatIdentity;
use App\Modules\Events\Http\Controllers\UchatBookingController;
use App\Modules\Events\Http\Controllers\UchatBookingPaymentController;
use App\Modules\Events\Http\Controllers\UchatEventController;
use App\Modules\Events\Http\Controllers\UchatEventInterestController;
use App\Modules\Finance\Http\Controllers\UchatPaymentController;
use App\Modules\Finance\Http\Controllers\UchatWalletController;
use App\Modules\Identity\Http\Controllers\UchatAccountController;
use App\Modules\Identity\Http\Controllers\UchatCustomerController;
use App\Modules\Identity\Http\Controllers\UchatDeletionRequestController;
use App\Modules\Identity\Http\Controllers\UchatFamilyMemberController;
use App\Modules\Store\Http\Controllers\UchatStoreController;
use Illuminate\Support\Facades\Route;

Route::post('webhooks/thawani', ThawaniPaymentWebhookController::class)
    ->name('api.webhooks.thawani');

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function (): void {
        Route::apiResource('events', EventController::class)->only(['index', 'show']);

        Route::middleware([LocalizeUchatResponse::class, AuthenticateUchat::class, 'throttle:uchat', ResolveUchatIdentity::class])
            ->prefix('integrations/uchat')
            ->name('integrations.uchat.')
            ->group(function (): void {
                Route::get('account', [UchatCustomerController::class, 'show'])->name('account.show');
                Route::post('account', [UchatCustomerController::class, 'store'])->middleware('throttle:10,1')->name('account.store');
                Route::patch('account', [UchatCustomerController::class, 'update'])->name('account.update');
                Route::get('account/deletion-requests', [UchatDeletionRequestController::class, 'index'])->name('account.deletion-requests.index');
                Route::post('account/deletion-requests', [UchatDeletionRequestController::class, 'store'])->middleware('throttle:5,1')->name('account.deletion-requests.store');
                Route::apiResource('family-members', UchatFamilyMemberController::class)->parameters(['family-members' => 'familyMember'])->whereNumber('familyMember');
                Route::get('events', [UchatEventController::class, 'index'])->name('events.index');
                Route::get('events/{event:slug}', [UchatEventController::class, 'show'])->name('events.show');
                Route::get('event-interests', [UchatEventInterestController::class, 'show'])->name('event-interests.show');
                Route::put('event-interests', [UchatEventInterestController::class, 'update'])->name('event-interests.update');
                Route::delete('event-interests', [UchatEventInterestController::class, 'destroy'])->name('event-interests.destroy');
                Route::get('bookings', [UchatBookingController::class, 'index'])->name('bookings.index');
                Route::post('bookings', [UchatBookingController::class, 'store'])->name('bookings.store');
                Route::get('bookings/{reference}', [UchatBookingController::class, 'show'])->name('bookings.show');
                Route::post('bookings/{reference}/payments', [UchatBookingPaymentController::class, 'store'])->name('bookings.payments.store');
                Route::get('payments', [UchatPaymentController::class, 'index'])->name('payments.index');
                Route::get('payments/{reference}', [UchatPaymentController::class, 'show'])->name('payments.show');
                Route::get('minor-profiles', [UchatAccountController::class, 'index'])->name('minor-profiles.index');
                Route::post('minor-profiles', [UchatAccountController::class, 'store'])->middleware('throttle:10,1')->name('minor-profiles.store');
                Route::post('minor-profiles/{minorProfile}/avatar', [UchatAccountController::class, 'updateAvatar'])->whereNumber('minorProfile')->middleware('throttle:10,1')->name('minor-profiles.avatar.update');
                Route::post('minor-profiles/{minorProfile}/activation-link', [UchatAccountController::class, 'activationLink'])->whereNumber('minorProfile')->middleware('throttle:5,1')->name('minor-profiles.activation-link');
                Route::post('minor-profiles/{minorProfile}/verification/send', [UchatAccountController::class, 'resend'])->whereNumber('minorProfile')->middleware('throttle:5,1')->name('minor-profiles.resend');
                Route::post('minor-profiles/{minorProfile}/verification/verify', [UchatAccountController::class, 'verify'])->whereNumber('minorProfile')->middleware('throttle:10,1')->name('minor-profiles.verify');
                Route::get('minor-profiles/{minorProfile}/wallet/status', [UchatAccountController::class, 'walletStatus'])->whereNumber('minorProfile')->name('minor-profiles.wallet.status');
                Route::post('minor-profiles/{minorProfile}/wallet-spending/enable', [UchatAccountController::class, 'enableWalletSpending'])->whereNumber('minorProfile')->name('minor-profiles.wallet-spending.enable');
                Route::post('minor-profiles/{minorProfile}/wallet-spending/disable', [UchatAccountController::class, 'disableWalletSpending'])->whereNumber('minorProfile')->name('minor-profiles.wallet-spending.disable');
                Route::post('minor-profiles/{minorProfile}/wallet/activate', [UchatAccountController::class, 'activateWallet'])->whereNumber('minorProfile')->name('minor-profiles.wallet.activate');
                Route::post('minor-profiles/{minorProfile}/wallet/suspend', [UchatAccountController::class, 'suspendWallet'])->whereNumber('minorProfile')->name('minor-profiles.wallet.suspend');
                Route::get('store/catalog', [UchatStoreController::class, 'catalog'])->name('store.catalog');
                Route::get('store/products/{slug}', [UchatStoreController::class, 'product'])->name('store.products.show');
                Route::get('store/cart', [UchatStoreController::class, 'cart'])->name('store.cart.show');
                Route::post('store/cart/items', [UchatStoreController::class, 'addCartItem'])->name('store.cart.items.store');
                Route::patch('store/cart/items/{sku}', [UchatStoreController::class, 'updateCartItem'])->name('store.cart.items.update');
                Route::delete('store/cart/items/{sku}', [UchatStoreController::class, 'removeCartItem'])->name('store.cart.items.destroy');
                Route::post('store/orders', [UchatStoreController::class, 'createOrder'])->name('store.orders.store');
                Route::post('store/orders/{reference}/payment', [UchatStoreController::class, 'initiatePayment'])->name('store.orders.payment.store');
                Route::get('wallet', [UchatWalletController::class, 'wallet'])->name('wallet.show');
                Route::get('wallet/movements', [UchatWalletController::class, 'walletMovements'])->name('wallet.movements');
                Route::get('wallet/top-ups/{reference}', [UchatWalletController::class, 'walletTopUpStatus'])->name('wallet.top-ups.show');
                Route::post('wallet/top-ups', [UchatWalletController::class, 'walletTopUp'])->name('wallet.top-ups.store');
                Route::get('store/orders', [UchatStoreController::class, 'orders'])->name('store.orders.index');
                Route::get('store/orders/{reference}', [UchatStoreController::class, 'order'])->name('store.orders.show');
            });
    });
