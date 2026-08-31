<?php

namespace App\Modules\Store\Providers;

use App\Modules\Finance\Events\PaymentSucceeded;
use App\Modules\Store\Events\OrderStateChanged;
use App\Modules\Store\Listeners\HandlePaymentSucceeded;
use App\Modules\Store\Listeners\NotifyMinorProfileOrderStatus;
use App\Modules\Store\Listeners\SendUchatOrderStateWebhook;
use App\Modules\Store\Models\Category;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use App\Modules\Store\Policies\CategoryPolicy;
use App\Modules\Store\Policies\OrderPolicy;
use App\Modules\Store\Policies\ProductOptionPolicy;
use App\Modules\Store\Policies\ProductPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class StoreServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(PaymentSucceeded::class, HandlePaymentSucceeded::class);
        Event::listen(OrderStateChanged::class, SendUchatOrderStateWebhook::class);
        Event::listen(OrderStateChanged::class, NotifyMinorProfileOrderStatus::class);
        Relation::morphMap(['store_order' => Order::class]);
        RateLimiter::for('uchat-store', function (Request $request) {
            $integrationFingerprint = hash('sha256', (string) config('byruhaa.uchat.api_token', 'unconfigured'));

            return Limit::perMinute((int) config('byruhaa.uchat.rate_limit', 60))
                ->by(hash('sha256', 'uchat-store|'.$integrationFingerprint.'|'.($request->ip() ?? 'unknown')));
        });
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(ProductOption::class, ProductOptionPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
    }
}
