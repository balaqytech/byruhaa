<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('store_orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('regular_total_baisa')->default(0)->after('total_baisa');
            $table->unsignedBigInteger('discount_baisa')->default(0)->after('regular_total_baisa');
            $table->string('pricing_tier', 32)->default('standard')->after('discount_baisa');
        });

        Schema::table('store_order_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('regular_unit_price_baisa')->default(0)->after('unit_price_baisa');
            $table->unsignedBigInteger('unit_discount_baisa')->default(0)->after('regular_unit_price_baisa');
            $table->unsignedBigInteger('line_discount_baisa')->default(0)->after('line_total_baisa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_order_items', function (Blueprint $table): void {
            $table->dropColumn(['regular_unit_price_baisa', 'unit_discount_baisa', 'line_discount_baisa']);
        });

        Schema::table('store_orders', function (Blueprint $table): void {
            $table->dropColumn(['regular_total_baisa', 'discount_baisa', 'pricing_tier']);
        });
    }
};
