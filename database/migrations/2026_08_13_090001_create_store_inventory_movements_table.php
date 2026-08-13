<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_inventory_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_option_id')->constrained('store_product_options')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('quantity_change');
            $table->unsignedInteger('stock_before');
            $table->unsignedInteger('stock_after');
            $table->string('reason');
            $table->timestamps();

            $table->index(['product_option_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_inventory_movements');
    }
};
