<?php

use App\Enums\AffiliateStatus;
use App\Http\Controllers\AffiliateAuthController;
use App\Http\Middleware\EnsureAffiliateIsApproved;
use App\Models\Affiliate;
use Illuminate\Support\Facades\Route;

Route::prefix('affiliate')
    ->name('affiliate.')
    ->group(function (): void {
        Route::get('login', [AffiliateAuthController::class, 'create'])->name('login');
        Route::post('login', [AffiliateAuthController::class, 'store'])->middleware('throttle:login')->name('login.store');
        Route::get('register', [AffiliateAuthController::class, 'register'])->name('register');
        Route::post('register', [AffiliateAuthController::class, 'createAffiliate'])->name('register.store');

        Route::middleware('auth:affiliate')->group(function (): void {
            Route::post('logout', [AffiliateAuthController::class, 'destroy'])->name('logout');

            Route::get('pending', function () {
                $affiliate = auth('affiliate')->user();

                abort_unless($affiliate instanceof Affiliate, 403);

                if ($affiliate->status === AffiliateStatus::Approved) {
                    return redirect()->route('affiliate.dashboard');
                }

                abort_unless($affiliate->status === AffiliateStatus::Pending, 403);

                return view('pages.affiliate.pending');
            })->name('pending');

            Route::middleware(EnsureAffiliateIsApproved::class)->group(function (): void {
                Route::livewire('dashboard', 'pages::affiliate.dashboard')->name('dashboard');
                Route::livewire('payouts/request', 'pages::affiliate.payout-request')->name('payouts.request');
            });
        });
    });
