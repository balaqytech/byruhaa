<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wallet_top_ups', function (Blueprint $table): void {
            $table->unsignedBigInteger('spendable_baisa')->default(0)->after('refundable_baisa');
        });

        DB::table('wallet_top_ups')->update(['spendable_baisa' => DB::raw('refundable_baisa')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_top_ups', function (Blueprint $table): void {
            $table->dropColumn('spendable_baisa');
        });
    }
};
