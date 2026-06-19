<?php

use App\Http\Controllers\Api\V1\CustomerBookingController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\CustomerFamilyMemberController;
use App\Http\Controllers\Api\V1\CustomerPaymentController;
use App\Http\Controllers\Api\V1\EventController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function (): void {
        Route::apiResource('customers', CustomerController::class);

        Route::scopeBindings()->group(function (): void {
            Route::apiResource('customers.bookings', CustomerBookingController::class)->only(['index', 'store', 'show']);
            Route::apiResource('customers.family-members', CustomerFamilyMemberController::class);
        });

        Route::apiResource('customers.payments', CustomerPaymentController::class);
        Route::apiResource('events', EventController::class)->only(['index', 'show']);
    });
