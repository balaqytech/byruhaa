<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('reference')->unique();
            $table->string('idempotency_key')->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('status')->default('pending_payment');
            $table->string('currency', 3)->default('OMR');
            $table->string('customer_name');
            $table->string('customer_phone', 32);
            $table->string('customer_email')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone', 32)->nullable();
            $table->text('note')->nullable();
            $table->string('pickup_type')->default('immediate');
            $table->timestamp('pickup_at')->nullable();
            $table->unsignedBigInteger('subtotal_baisa')->default(0);
            $table->unsignedBigInteger('vat_baisa')->default(0);
            $table->unsignedBigInteger('total_baisa')->default(0);
            $table->timestamps();

            $table->index(['status', 'pickup_at']);
            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_orders');
    }
};
