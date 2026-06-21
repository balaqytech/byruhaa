<?php

use App\Http\Controllers\ThawaniPaymentReturnController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/public.php';

Route::middleware('signed')->group(function () {
    Route::get('payments/thawani/{payment}/success', [ThawaniPaymentReturnController::class, 'success'])->name('payments.thawani.success');
    Route::get('payments/thawani/{payment}/cancel', [ThawaniPaymentReturnController::class, 'cancel'])->name('payments.thawani.cancel');
});

require __DIR__.'/customer.php';
