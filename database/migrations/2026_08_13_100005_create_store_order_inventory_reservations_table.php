<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_order_inventory_reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('store_orders')->cascadeOnDelete();
            $table->foreignId('reservation_id')->constrained('store_inventory_reservations')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(
                ['order_id', 'reservation_id'],
                'store_order_reservations_order_reservation_unique',
            );
            $table->unique('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_order_inventory_reservations');
    }
};
