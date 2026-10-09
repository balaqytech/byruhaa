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
            $table->foreignId('pos_cashier_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->char('pos_request_hash', 64)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('pos_cashier_user_id');
            $table->dropColumn('pos_request_hash');
        });
    }
};
