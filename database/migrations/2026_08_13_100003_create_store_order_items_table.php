<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('store_orders')->cascadeOnDelete();
            $table->foreignId('product_option_id')->nullable()->constrained('store_product_options')->nullOnDelete();
            $table->string('product_name');
            $table->string('option_name');
            $table->string('sku');
            $table->string('currency', 3)->default('OMR');
            $table->unsignedBigInteger('unit_price_baisa');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('vat_baisa')->default(0);
            $table->unsignedBigInteger('line_subtotal_baisa');
            $table->unsignedBigInteger('line_total_baisa');
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->index('product_option_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_order_items');
    }
};
