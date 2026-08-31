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
            $table->foreignId('minor_profile_id')->nullable()->after('customer_id')->constrained('minor_profiles')->nullOnDelete();
            $table->index(['minor_profile_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_orders', function (Blueprint $table): void {
            $table->dropForeign(['minor_profile_id']);
            $table->dropIndex(['minor_profile_id', 'created_at']);
            $table->dropColumn('minor_profile_id');
        });
    }
};
