<?php

use App\Http\Controllers\Api\V1\CustomerBookingController;
use App\Http\Controllers\Api\V1\CustomerBookingPaymentController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\CustomerFamilyMemberController;
use App\Http\Controllers\Api\V1\CustomerPaymentController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\ThawaniPaymentWebhookController;
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
    });
