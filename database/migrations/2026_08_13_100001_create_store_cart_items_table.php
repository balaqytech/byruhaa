<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_cart_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('cart_id')->constrained('store_carts')->cascadeOnDelete();
            $table->foreignId('product_option_id')->constrained('store_product_options')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->unique(['cart_id', 'product_option_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_cart_items');
    }
};
