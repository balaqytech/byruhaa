<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

uses(TestCase::class);

test('public pages migration is reversible and indexed for publication', function (): void {
    $originalDefault = (string) config('database.default');
    $originalSqliteDatabase = config('database.connections.sqlite.database');

    Config::set('database.default', 'sqlite');
    Config::set('database.connections.sqlite.database', ':memory:');
    DB::purge('sqlite');
    DB::setDefaultConnection('sqlite');

    try {
        $migration = require base_path('database/migrations/2026_08_21_000002_create_public_pages_table.php');
        $migration->up();

        expect(Schema::hasTable('public_pages'))->toBeTrue()
            ->and(Schema::hasColumn('public_pages', 'key'))->toBeTrue()
            ->and(Schema::hasColumn('public_pages', 'content'))->toBeTrue()
            ->and(collect(Schema::getIndexes('public_pages'))->pluck('name')->all())
            ->toContain('public_pages_visibility_index');

        $migration->down();

        expect(Schema::hasTable('public_pages'))->toBeFalse();
    } finally {
        DB::disconnect('sqlite');
        Config::set('database.default', $originalDefault);
        Config::set('database.connections.sqlite.database', $originalSqliteDatabase);
        DB::purge('sqlite');
        DB::setDefaultConnection($originalDefault);
    }
});
