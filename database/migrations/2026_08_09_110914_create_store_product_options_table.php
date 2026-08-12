<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_product_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('store_products')->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->unique();
            $table->unsignedBigInteger('price_baisa');
            $table->char('currency', 3)->default('OMR');
            $table->foreignId('image_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_available')->default(true);
            $table->boolean('is_default')->nullable()->default(null);
            $table->timestamps();

            $table->index(['product_id', 'is_available']);
            $table->unique(['product_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_product_options');
    }
};
