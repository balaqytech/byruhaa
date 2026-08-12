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

        $migrationPaths = [
            'database/migrations/2026_08_09_110912_create_store_categories_table.php',
            'database/migrations/2026_08_09_110913_create_store_products_table.php',
            'database/migrations/2026_08_09_110914_create_store_product_options_table.php',
        ];
        $migrations = [];

        foreach ($migrationPaths as $migrationPath) {
            $migration = require base_path($migrationPath);
            $migration->up();
            $migrations[] = $migration;
        }

        expect(Schema::hasTable('store_categories'))->toBeTrue()
            ->and(Schema::hasTable('store_products'))->toBeTrue()
            ->and(Schema::hasTable('store_product_options'))->toBeTrue();

        foreach (array_reverse($migrations) as $migration) {
            $migration->down();
        }

        expect(Schema::hasTable('store_categories'))->toBeFalse()
            ->and(Schema::hasTable('store_products'))->toBeFalse()
            ->and(Schema::hasTable('store_product_options'))->toBeFalse();
    } finally {
        DB::disconnect('sqlite');
        Config::set('database.default', $originalDefault);
        Config::set('database.connections.sqlite.database', $originalSqliteDatabase);
        DB::purge('sqlite');
        DB::setDefaultConnection($originalDefault);
    }
});
