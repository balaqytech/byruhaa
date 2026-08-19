<?php

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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
            'database/migrations/2026_08_13_190000_add_uchat_owner_key_to_store_carts_table.php',
        ];
        $migrations = [];
        $legacyCartId = null;

        foreach ($migrationPaths as $migrationPath) {
            if ($migrationPath === 'database/migrations/2026_08_13_190000_add_uchat_owner_key_to_store_carts_table.php') {
                $legacyCartId = DB::table('store_carts')->insertGetId([
                    'token' => (string) Str::uuid(),
                    'customer_id' => null,
                    'last_activity_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

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
        expect(Schema::hasColumn('store_carts', 'uchat_owner_key'))->toBeTrue();
        expect(DB::table('store_carts')->where('id', $legacyCartId)->exists())->toBeTrue();
        expect(collect(Schema::getIndexes('store_inventory_reservation_items'))->pluck('name')->all())
            ->toContain('store_reservation_items_reservation_option_unique');
        expect(collect(Schema::getIndexes('store_order_inventory_reservations'))->pluck('name')->all())
            ->toContain('store_order_reservations_order_reservation_unique');

        DB::table('store_carts')->insert([
            'token' => (string) Str::uuid(),
            'customer_id' => null,
            'uchat_owner_key' => str_repeat('a', 64),
            'last_activity_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        expect(fn () => DB::table('store_carts')->insert([
            'token' => (string) Str::uuid(),
            'customer_id' => null,
            'uchat_owner_key' => str_repeat('a', 64),
            'last_activity_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]))->toThrow(QueryException::class);
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
