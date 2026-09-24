<?php

use App\Modules\Finance\Http\Controllers\WalletController;
use App\Modules\Identity\Http\Controllers\MinorProfileController;
use App\Modules\Store\Http\Controllers\CustomerOrderReceiptController;
use App\Modules\Store\Http\Controllers\WalletOrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:customer'])
    ->prefix('customer')
    ->name('customer.')
    ->group(function (): void {
        Route::view('dashboard', 'pages.customer.dashboard')->name('dashboard');
        Route::livewire('events', 'pages::customer.events.index')->name('events.index');
        Route::livewire('events/{event:slug}', 'pages::customer.events.show')->name('events.show');
        Route::livewire('interests', 'pages::customer.interests.index')->name('interests.index');
        Route::livewire('family-members', 'pages::customer.family-members.index')->name('family-members.index');
        Route::get('minor-profiles', [MinorProfileController::class, 'index'])->name('minor-profiles.index');
        Route::post('minor-profiles', [MinorProfileController::class, 'store'])->name('minor-profiles.store');
        Route::post('minor-profiles/{minorProfile}/activation-link', [MinorProfileController::class, 'activationLink'])->middleware('throttle:10,1')->name('minor-profiles.activation-link');
        Route::post('minor-profiles/{minorProfile}/verify', [MinorProfileController::class, 'verify'])->name('minor-profiles.verify');
        Route::post('minor-profiles/{minorProfile}/suspend', [MinorProfileController::class, 'suspend'])->name('minor-profiles.suspend');
        Route::post('minor-profiles/{minorProfile}/resume', [MinorProfileController::class, 'resume'])->name('minor-profiles.resume');
        Route::post('minor-profiles/{minorProfile}/direct-payment', [MinorProfileController::class, 'toggleDirectPayment'])->name('minor-profiles.direct-payment');
        Route::post('minor-profiles/{minorProfile}/wallet-spending', [MinorProfileController::class, 'toggleWalletSpending'])->name('minor-profiles.wallet-spending');
        Route::get('minor-profiles/{minorProfile}/wallet', [WalletController::class, 'show'])->name('minor-profiles.wallet');
        Route::post('minor-profiles/{minorProfile}/wallet/top-up', [WalletController::class, 'topUp'])->middleware('throttle:10,1')->name('minor-profiles.wallet.top-up');
        Route::get('minor-profiles/{minorProfile}/wallet/top-ups/{operationKey}/success', [WalletController::class, 'success'])
            ->middleware('signed')
            ->name('minor-profiles.wallet.top-up.success');
        Route::get('minor-profiles/{minorProfile}/wallet/top-ups/{operationKey}/cancel', [WalletController::class, 'cancel'])
            ->middleware('signed')
            ->name('minor-profiles.wallet.top-up.cancel');
        Route::post('minor-profiles/{minorProfile}/delete-request', [MinorProfileController::class, 'requestDeletion'])->name('minor-profiles.delete-request');
        Route::livewire('bookings', 'pages::customer.bookings.index')->name('bookings.index');
        Route::livewire('bookings/{booking}/contracts/{contract}', 'pages::customer.bookings.contract')->name('bookings.contracts.show');
        Route::livewire('bookings/{booking}', 'pages::customer.bookings.show')->name('bookings.show');
        Route::livewire('payments', 'pages::customer.payments.index')->name('payments.index');
        Route::livewire('store/orders', 'pages::customer.store.orders.index')->name('store.orders.index');
        Route::livewire('store/orders/{order:payment_token}', 'pages::customer.store.orders.show')
            ->middleware('throttle:60,1')
            ->name('store.orders.show');
        Route::get('store/orders/{order:payment_token}/receipt', [CustomerOrderReceiptController::class, 'html'])
            ->name('store.orders.receipt');
        Route::get('store/orders/{order:payment_token}/receipt.pdf', [CustomerOrderReceiptController::class, 'pdf'])
            ->name('store.orders.receipt.pdf');
        Route::post('store/orders/{order:payment_token}/wallet', [WalletOrderController::class, 'confirm'])
            ->middleware('throttle:10,1')
            ->name('store.orders.wallet.confirm');

        Route::get('settings', fn () => redirect()->route('customer.profile.edit'))->name('settings');
        Route::livewire('settings/profile', 'pages::customer.settings.profile')->name('profile.edit');
        Route::livewire('settings/appearance', 'pages::customer.settings.appearance')->name('appearance.edit');
        Route::livewire('settings/security', 'pages::customer.settings.security')->name('security.edit');
    });
