<?php

use App\Modules\Finance\Models\Wallet;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Store\Models\Order;
use Database\Seeders\DemoStoreOrdersSeeder;
use Database\Seeders\StoreCatalogSeeder;

test('demo orders include customer and minor wallet purchases without financial side effects', function (): void {
    $this->seed(StoreCatalogSeeder::class);
    $this->seed(DemoStoreOrdersSeeder::class);

    expect(Order::query()->where('idempotency_key', 'like', 'DEMO-ORD-%')->count())->toBe(8)
        ->and(Order::query()->where('payment_method', 'thawani')->count())->toBe(4)
        ->and(Order::query()->where('payment_method', 'wallet')->count())->toBe(4)
        ->and(Order::query()->where('payment_method', 'wallet')->whereNull('minor_profile_id')->count())->toBe(0)
        ->and(Order::query()->where('payment_method', 'wallet')->whereNull('paid_at')->count())->toBe(0)
        ->and(Order::query()->whereDoesntHave('items')->count())->toBe(0)
        ->and(Order::query()->whereDoesntHave('statusHistory')->count())->toBe(0)
        ->and(Customer::query()->count())->toBe(2)
        ->and(MinorProfile::query()->count())->toBe(2)
        ->and(Wallet::query()->count())->toBe(2)
        ->and(Wallet::query()->sum('balance_baisa'))->toBe(0);

    $this->seed(DemoStoreOrdersSeeder::class);

    expect(Order::query()->count())->toBe(8)
        ->and(Customer::query()->count())->toBe(2)
        ->and(MinorProfile::query()->count())->toBe(2);
});
