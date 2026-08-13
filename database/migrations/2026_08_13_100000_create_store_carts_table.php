<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_carts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('token')->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_carts');
    }
};
