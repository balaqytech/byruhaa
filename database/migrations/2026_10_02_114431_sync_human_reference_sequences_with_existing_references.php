<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $highestSequenceId = (int) DB::table('human_reference_sequences')->max('id');
        $highestReference = 10_000_000 + $highestSequenceId;

        foreach ([
            'store_orders',
            'bookings',
            'payments',
            'payment_refunds',
            'ledger_transactions',
            'wallet_top_ups',
            'affiliate_payout_requests',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (DB::table($table)->select(['id', 'reference'])->lazyById(500) as $row) {
                if (ctype_digit((string) $row->reference)) {
                    $highestReference = max($highestReference, (int) $row->reference);
                }
            }
        }

        $targetSequenceId = $highestReference - 10_000_000;

        if ($targetSequenceId > $highestSequenceId) {
            DB::table('human_reference_sequences')->insert([
                'id' => $targetSequenceId,
                'type' => 'legacy_reference_sync',
                'created_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Sequence values must never be rewound.
    }
};
