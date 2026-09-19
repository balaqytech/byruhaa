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
        Schema::table('minor_profiles', function (Blueprint $table) {
            $table->boolean('wallet_spending_enabled')->default(false)->after('direct_payment_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('minor_profiles', function (Blueprint $table) {
            $table->dropColumn('wallet_spending_enabled');
        });
    }
};
