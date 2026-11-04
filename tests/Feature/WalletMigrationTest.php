<?php

use App\Modules\Finance\Models\Wallet;
use App\Modules\Finance\Models\WalletTopUp;
use App\Modules\Identity\Models\MinorProfile;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('wallet indexes and foreign key names fit the MySQL identifier limit', function (): void {
    foreach (['wallets', 'wallet_top_ups', 'wallet_movements', 'wallet_purchase_allocations', 'wallet_settlements'] as $table) {
        foreach ([...Schema::getIndexes($table), ...Schema::getForeignKeys($table)] as $constraint) {
            expect(strlen($constraint['name'] ?? ''))->toBeLessThanOrEqual(64);
        }
    }

    expect(Schema::hasIndex('wallet_purchase_allocations', ['wallet_top_up_id', 'order_reference'], 'unique'))->toBeTrue()
        ->and(Schema::hasIndex('wallet_purchase_allocations', ['wallet_id', 'order_reference']))->toBeTrue();
});

test('wallet allocation migration resumes missing indexes without losing existing allocations', function (): void {
    $wallet = Wallet::query()->create(['minor_profile_id' => MinorProfile::factory()->create()->id, 'currency' => 'OMR']);
    $topUp = WalletTopUp::query()->create([
        'wallet_id' => $wallet->id,
        'operation_key' => 'migration-recovery',
        'amount_baisa' => 1000,
        'currency' => 'OMR',
    ]);
    $allocationId = DB::table('wallet_purchase_allocations')->insertGetId([
        'wallet_id' => $wallet->id,
        'wallet_top_up_id' => $topUp->id,
        'order_reference' => 'ORDER-MIGRATION-RECOVERY',
        'amount_baisa' => 1000,
    ]);

    Schema::table('wallet_purchase_allocations', function (Blueprint $table): void {
        $table->dropUnique('wallet_allocations_top_up_order_unique');
        $table->dropIndex('wallet_allocations_wallet_order_index');
    });

    $migration = require database_path('migrations/2026_09_15_063032_create_wallet_purchase_allocations_table.php');
    $migration->up();
    $migration->up();

    expect(Schema::hasIndex('wallet_purchase_allocations', ['wallet_top_up_id', 'order_reference'], 'unique'))->toBeTrue()
        ->and(Schema::hasIndex('wallet_purchase_allocations', ['wallet_id', 'order_reference']))->toBeTrue();
    $this->assertDatabaseHas('wallet_purchase_allocations', ['id' => $allocationId, 'amount_baisa' => 1000]);
});
