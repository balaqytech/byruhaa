<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

test('store catalog migrations run up and down cleanly', function (): void {
    $originalDefault = (string) config('database.default');
    $originalSqliteDatabase = config('database.connections.sqlite.database');

    Config::set('database.default', 'sqlite');
    Config::set('database.connections.sqlite.database', ':memory:');
    DB::purge('sqlite');
    DB::setDefaultConnection('sqlite');

    try {
        Schema::create('media_files', function (Blueprint $table): void {
            $table->id();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
        });

        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
        });

        $migrationPaths = [
            'database/migrations/2026_08_09_110912_create_store_categories_table.php',
            'database/migrations/2026_08_09_110913_create_store_products_table.php',
            'database/migrations/2026_08_09_110914_create_store_product_options_table.php',
            'database/migrations/2026_08_13_090000_add_inventory_tracking_to_store_product_options_table.php',
            'database/migrations/2026_08_13_090001_create_store_inventory_movements_table.php',
            'database/migrations/2026_08_13_090002_create_store_inventory_reservations_table.php',
            'database/migrations/2026_08_13_090003_create_store_inventory_reservation_items_table.php',
            'database/migrations/2026_08_13_100000_create_store_carts_table.php',
            'database/migrations/2026_08_13_100001_create_store_cart_items_table.php',
            'database/migrations/2026_08_13_100002_create_store_orders_table.php',
            'database/migrations/2026_08_13_100003_create_store_order_items_table.php',
            'database/migrations/2026_08_13_100004_create_store_order_status_histories_table.php',
            'database/migrations/2026_08_13_100005_create_store_order_inventory_reservations_table.php',
            'database/migrations/2026_08_13_103809_add_payment_token_to_store_orders_table.php',
            'database/migrations/2026_08_13_150123_add_receipt_snapshots_to_store_orders_table.php',
        ];
        $migrations = [];

        foreach ($migrationPaths as $migrationPath) {
            $migration = require base_path($migrationPath);
            $migration->up();
            $migrations[] = $migration;
        }

        expect(Schema::hasTable('store_categories'))->toBeTrue()
            ->and(Schema::hasTable('store_products'))->toBeTrue()
            ->and(Schema::hasTable('store_product_options'))->toBeTrue()
            ->and(Schema::hasTable('store_carts'))->toBeTrue()
            ->and(Schema::hasTable('store_cart_items'))->toBeTrue()
            ->and(Schema::hasTable('store_orders'))->toBeTrue()
            ->and(Schema::hasTable('store_order_items'))->toBeTrue()
            ->and(Schema::hasTable('store_order_status_histories'))->toBeTrue()
            ->and(Schema::hasTable('store_order_inventory_reservations'))->toBeTrue();

        expect(Schema::hasColumn('store_orders', 'payment_token'))->toBeTrue();
        expect(Schema::hasColumn('store_orders', 'vat_rate_percentage'))->toBeTrue()
            ->and(Schema::hasColumn('store_orders', 'paid_at'))->toBeTrue()
            ->and(Schema::hasColumn('store_orders', 'provider_invoice'))->toBeTrue();

        foreach (array_reverse($migrations) as $migration) {
            $migration->down();
        }

        expect(Schema::hasTable('store_categories'))->toBeFalse()
            ->and(Schema::hasTable('store_products'))->toBeFalse()
            ->and(Schema::hasTable('store_product_options'))->toBeFalse()
            ->and(Schema::hasTable('store_inventory_movements'))->toBeFalse()
            ->and(Schema::hasTable('store_inventory_reservations'))->toBeFalse()
            ->and(Schema::hasTable('store_inventory_reservation_items'))->toBeFalse()
            ->and(Schema::hasTable('store_carts'))->toBeFalse()
            ->and(Schema::hasTable('store_cart_items'))->toBeFalse()
            ->and(Schema::hasTable('store_orders'))->toBeFalse()
            ->and(Schema::hasTable('store_order_items'))->toBeFalse()
            ->and(Schema::hasTable('store_order_status_histories'))->toBeFalse()
            ->and(Schema::hasTable('store_order_inventory_reservations'))->toBeFalse();
    } finally {
        DB::disconnect('sqlite');
        Config::set('database.default', $originalDefault);
        Config::set('database.connections.sqlite.database', $originalSqliteDatabase);
        DB::purge('sqlite');
        DB::setDefaultConnection($originalDefault);
    }
});
