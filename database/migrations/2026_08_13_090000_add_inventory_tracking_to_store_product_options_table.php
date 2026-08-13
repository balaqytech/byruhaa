<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_product_options', function (Blueprint $table): void {
            $table->boolean('tracks_inventory')->default(false)->after('is_available');
            $table->unsignedInteger('stock_on_hand')->default(0)->after('tracks_inventory');
        });
    }

    public function down(): void
    {
        Schema::table('store_product_options', function (Blueprint $table): void {
            $table->dropColumn(['tracks_inventory', 'stock_on_hand']);
        });
    }
};
