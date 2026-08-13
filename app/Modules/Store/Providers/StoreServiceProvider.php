<?php

namespace App\Modules\Store\Providers;

use App\Modules\Finance\Events\PaymentSucceeded;
use App\Modules\Store\Listeners\HandlePaymentSucceeded;
use App\Modules\Store\Models\Category;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use App\Modules\Store\Policies\CategoryPolicy;
use App\Modules\Store\Policies\OrderPolicy;
use App\Modules\Store\Policies\ProductOptionPolicy;
use App\Modules\Store\Policies\ProductPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class StoreServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(PaymentSucceeded::class, HandlePaymentSucceeded::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(ProductOption::class, ProductOptionPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
    }
}
