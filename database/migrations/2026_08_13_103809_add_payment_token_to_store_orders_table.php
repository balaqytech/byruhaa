<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('store_orders', function (Blueprint $table): void {
            $table->uuid('payment_token')->nullable()->unique()->after('reference');
        });

        DB::table('store_orders')
            ->whereNull('payment_token')
            ->orderBy('id')
            ->eachById(function (object $order): void {
                DB::table('store_orders')->where('id', $order->id)->update([
                    'payment_token' => (string) Str::uuid(),
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_orders', function (Blueprint $table): void {
            $table->dropUnique(['payment_token']);
            $table->dropColumn('payment_token');
        });
    }
};
