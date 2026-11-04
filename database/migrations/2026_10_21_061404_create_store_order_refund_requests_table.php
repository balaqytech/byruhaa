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
        Schema::create('store_order_refund_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('store_orders')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('paid_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('payment_refund_id')->nullable()->constrained('payment_refunds')->nullOnDelete();
            $table->string('reference', 32)->unique();
            $table->string('operation_key', 100)->unique();
            $table->string('status', 32)->default('pending');
            $table->string('reason', 255);
            $table->unsignedBigInteger('amount_baisa');
            $table->unsignedBigInteger('subtotal_baisa');
            $table->unsignedBigInteger('vat_baisa');
            $table->char('currency', 3)->default('OMR');
            $table->string('payout_reference')->nullable()->unique();
            $table->string('evidence_path')->nullable();
            $table->text('review_note')->nullable();
            $table->boolean('admin_window_override')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status']);
        });

        Schema::create('store_order_refund_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_order_refund_request_id')->constrained('store_order_refund_requests')->restrictOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('store_order_items')->nullOnDelete();
            $table->unsignedInteger('quantity')->nullable();
            $table->unsignedBigInteger('amount_baisa');
            $table->unsignedBigInteger('subtotal_baisa');
            $table->unsignedBigInteger('vat_baisa');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_order_refund_lines');
        Schema::dropIfExists('store_order_refund_requests');
    }
};
