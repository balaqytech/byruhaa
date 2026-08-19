<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_inventory_reservation_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reservation_id')->constrained('store_inventory_reservations')->cascadeOnDelete();
            $table->foreignId('product_option_id')->constrained('store_product_options')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();

            $table->unique(
                ['reservation_id', 'product_option_id'],
                'store_reservation_items_reservation_option_unique',
            );
            $table->index('product_option_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_inventory_reservation_items');
    }
};
