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
            $table->string('customer_phone', 32)->nullable()->change();
            $table->unsignedBigInteger('cash_received_baisa')->nullable()->after('payment_reference');
            $table->unsignedBigInteger('cash_change_baisa')->nullable()->after('cash_received_baisa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_orders', function (Blueprint $table): void {
            $table->dropColumn(['cash_received_baisa', 'cash_change_baisa']);
            $table->string('customer_phone', 32)->nullable(false)->change();
        });
    }
};
