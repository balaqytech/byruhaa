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
        Schema::table('store_product_options', function (Blueprint $table): void {
            $table->unsignedBigInteger('member_price_baisa')->nullable()->after('price_baisa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_product_options', function (Blueprint $table): void {
            $table->dropColumn('member_price_baisa');
        });
    }
};
