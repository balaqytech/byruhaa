<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('store_orders')->update([
            'regular_total_baisa' => DB::raw('total_baisa'),
            'discount_baisa' => 0,
            'pricing_tier' => 'standard',
        ]);

        DB::table('store_order_items')->update([
            'regular_unit_price_baisa' => DB::raw('unit_price_baisa'),
            'unit_discount_baisa' => 0,
            'line_discount_baisa' => 0,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Snapshot backfills are intentionally preserved until their columns are rolled back.
    }
};
