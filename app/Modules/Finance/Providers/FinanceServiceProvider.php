<?php

namespace App\Modules\Finance\Providers;

use App\Modules\Finance\Contracts\PaymentGateway;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class FinanceServiceProvider extends ServiceProvider
{
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
