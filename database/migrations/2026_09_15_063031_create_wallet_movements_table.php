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
        Schema::create('wallet_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->restrictOnDelete();
            $table->foreignId('wallet_top_up_id')->nullable()->constrained('wallet_top_ups')->nullOnDelete();
            $table->string('operation_key', 120)->unique();
            $table->string('type', 32)->index();
            $table->string('order_reference', 255)->nullable()->index();
            $table->unsignedBigInteger('credit_baisa')->default(0);
            $table->unsignedBigInteger('debit_baisa')->default(0);
            $table->unsignedBigInteger('balance_after_baisa');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['wallet_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_movements');
    }
};
