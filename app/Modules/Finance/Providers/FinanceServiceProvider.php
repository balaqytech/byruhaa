<?php

namespace App\Modules\Finance\Providers;

use App\Modules\Finance\Contracts\PaymentGateway;
use App\Modules\Finance\Models\LedgerTransaction;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\PaymentRefund;
use App\Modules\Finance\Services\Payments\PaymentGatewayManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class FinanceServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap Finance services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'App\\Models\\LedgerTransaction' => LedgerTransaction::class,
            'App\\Models\\Payment' => Payment::class,
            'App\\Models\\PaymentRefund' => PaymentRefund::class,
        ]);
    }

    /**
     * Register Finance services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayManager::class);

        $this->app->bind(PaymentGateway::class, function (Application $app): PaymentGateway {
            $gateway = $app->make(PaymentGatewayManager::class)->driver();

            if (! $gateway instanceof PaymentGateway) {
                throw new \RuntimeException('The configured payment gateway is invalid.');
            }

            return $gateway;
        });
    }
}
