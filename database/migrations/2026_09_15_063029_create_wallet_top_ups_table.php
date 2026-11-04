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
        Schema::create('wallet_top_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->restrictOnDelete();
            $table->foreignId('payment_id')->nullable()->unique()->constrained('payments')->nullOnDelete();
            $table->string('reference', 32)->unique();
            $table->string('operation_key', 100)->unique();
            $table->string('status', 32)->default('pending')->index();
            $table->string('currency', 3)->default('OMR');
            $table->unsignedBigInteger('amount_baisa');
            $table->unsignedBigInteger('refundable_baisa')->default(0);
            $table->timestamp('credited_at')->nullable();
            $table->timestamp('refund_deadline_at')->nullable();
            $table->timestamps();

            $table->index(['wallet_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_top_ups');
    }
};
