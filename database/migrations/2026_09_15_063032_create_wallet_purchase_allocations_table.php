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
        if (! Schema::hasTable('wallet_purchase_allocations')) {
            Schema::create('wallet_purchase_allocations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('wallet_id')->constrained('wallets')->restrictOnDelete();
                $table->foreignId('wallet_top_up_id')->constrained('wallet_top_ups')->restrictOnDelete();
                $table->string('order_reference', 255);
                $table->unsignedBigInteger('amount_baisa');
                $table->timestamp('refund_deadline_at')->nullable();
                $table->timestamp('reversed_at')->nullable();
                $table->timestamps();

            });
        }

        if (! Schema::hasIndex('wallet_purchase_allocations', ['wallet_top_up_id', 'order_reference'], 'unique')) {
            Schema::table('wallet_purchase_allocations', function (Blueprint $table): void {
                $table->unique(['wallet_top_up_id', 'order_reference'], 'wallet_allocations_top_up_order_unique');
            });
        }

        if (! Schema::hasIndex('wallet_purchase_allocations', ['wallet_id', 'order_reference'])) {
            Schema::table('wallet_purchase_allocations', function (Blueprint $table): void {
                $table->index(['wallet_id', 'order_reference'], 'wallet_allocations_wallet_order_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_purchase_allocations');
    }
};
