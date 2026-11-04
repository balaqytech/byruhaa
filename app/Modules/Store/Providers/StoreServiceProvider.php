<?php

namespace App\Modules\Store\Providers;

use App\Modules\Finance\Events\PaymentSucceeded;
use App\Modules\Store\Actions\CreateAdminOrder;
use App\Modules\Store\Contracts\PosOrderCheckout;
use App\Modules\Store\Contracts\ProductOptionPricingRule;
use App\Modules\Store\Events\OrderStateChanged;
use App\Modules\Store\Listeners\HandlePaymentSucceeded;
use App\Modules\Store\Listeners\NotifyMinorProfileOrderStatus;
use App\Modules\Store\Models\Category;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use App\Modules\Store\Policies\CategoryPolicy;
use App\Modules\Store\Policies\OrderPolicy;
use App\Modules\Store\Policies\ProductOptionPolicy;
use App\Modules\Store\Policies\ProductPolicy;
use App\Modules\Store\Services\PricingContextResolver;
use App\Modules\Store\Services\PricingRules\MemberPriceRule;
use App\Modules\Store\Services\StorePricing;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class StoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PosOrderCheckout::class, CreateAdminOrder::class);
        $this->app->tag([MemberPriceRule::class], ProductOptionPricingRule::class);

        $this->app->singleton(StorePricing::class, fn (Application $app): StorePricing => new StorePricing(
            contexts: $app->make(PricingContextResolver::class),
            rules: $app->tagged(ProductOptionPricingRule::class),
        ));
    }

    public function boot(): void
    {
        Event::listen(PaymentSucceeded::class, HandlePaymentSucceeded::class);
        Event::listen(OrderStateChanged::class, NotifyMinorProfileOrderStatus::class);
        Relation::morphMap(['store_order' => Order::class]);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(ProductOption::class, ProductOptionPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
    }
}
