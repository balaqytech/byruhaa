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
        Schema::create('wallet_settlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->restrictOnDelete();
            $table->string('order_reference', 255)->unique();
            $table->unsignedBigInteger('amount_baisa');
            $table->string('currency', 3)->default('OMR');
            $table->string('status', 32)->default('eligible')->index();
            $table->timestamp('eligible_at');
            $table->timestamp('transferred_at')->nullable();
            $table->string('transfer_reference', 255)->nullable();
            $table->timestamps();

            $table->index(['wallet_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_settlements');
    }
};
