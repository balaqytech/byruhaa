<?php

use App\Http\Controllers\Api\V1\AssistantEventInterestController;
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

        Route::middleware('throttle:20,1')
            ->prefix('integrations/assistant')
            ->name('integrations.assistant.')
            ->group(function (): void {
                Route::get('event-interests', [AssistantEventInterestController::class, 'show'])->name('event-interests.show');
                Route::put('event-interests', [AssistantEventInterestController::class, 'update'])->name('event-interests.update');
                Route::delete('event-interests', [AssistantEventInterestController::class, 'destroy'])->name('event-interests.destroy');
            });
    });
