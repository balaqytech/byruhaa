<?php

namespace App\Modules\Finance\Providers;

use App\Modules\Finance\Commands\ReconcileWalletBalances;
use App\Modules\Finance\Contracts\PaymentGateway;
use App\Modules\Finance\Contracts\PaymentService;
use App\Modules\Finance\Contracts\WalletService;
use App\Modules\Finance\Events\PaymentSucceeded;
use App\Modules\Finance\Events\WalletMovementPosted;
use App\Modules\Finance\Listeners\CreditWalletTopUp;
use App\Modules\Finance\Listeners\SendWalletMovementNotification;
use App\Modules\Finance\Models\LedgerTransaction;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\PaymentRefund;
use App\Modules\Finance\Services\DatabaseWalletService;
use App\Modules\Finance\Services\Payments\PaymentGatewayManager;
use App\Modules\Finance\Services\Payments\ThawaniPaymentService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class FinanceServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap Finance services.
     */
    public function boot(): void
    {
        Event::listen(PaymentSucceeded::class, CreditWalletTopUp::class);
        Event::listen(WalletMovementPosted::class, SendWalletMovementNotification::class);

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
        $this->commands([ReconcileWalletBalances::class]);
        $this->app->singleton(PaymentGatewayManager::class);
        $this->app->bind(PaymentService::class, ThawaniPaymentService::class);
        $this->app->bind(WalletService::class, DatabaseWalletService::class);

        $this->app->bind(PaymentGateway::class, function (Application $app): PaymentGateway {
            $gateway = $app->make(PaymentGatewayManager::class)->driver();

            if (! $gateway instanceof PaymentGateway) {
                throw new \RuntimeException('The configured payment gateway is invalid.');
            }

            return $gateway;
        });
    }
}
