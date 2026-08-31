<?php

use App\Http\Middleware\AuthenticateMinorProfile;
use App\Modules\Identity\Http\Controllers\MinorProfileAuthController;
use App\Modules\Store\Http\Controllers\MinorProfileStoreController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:10,1')->group(function (): void {
    Route::get('minor/login', [MinorProfileAuthController::class, 'showLogin'])->name('minor.login');
    Route::post('minor/login', [MinorProfileAuthController::class, 'login'])->name('minor.login.store');
    Route::get('minor/activate/{minorProfile}/{token}', [MinorProfileAuthController::class, 'showActivation'])
        ->middleware('signed')
        ->name('minor.activate');
    Route::post('minor/activate/{minorProfile}/{token}', [MinorProfileAuthController::class, 'activate'])
        ->middleware('signed')
        ->name('minor.activate.store');
});

Route::middleware(['auth:minor-profile', AuthenticateMinorProfile::class])
    ->prefix('minor')
    ->name('minor.')
    ->group(function (): void {
        Route::get('dashboard', [MinorProfileStoreController::class, 'dashboard'])->name('dashboard');
        Route::get('orders', [MinorProfileStoreController::class, 'orders'])->name('orders.index');
        Route::get('orders/{order:payment_token}', [MinorProfileStoreController::class, 'show'])->name('orders.show');
        Route::post('orders/{order:payment_token}/payment', [MinorProfileStoreController::class, 'pay'])->middleware('throttle:10,1')->name('orders.payment');
        Route::post('logout', [MinorProfileAuthController::class, 'logout'])->name('logout');
    });
