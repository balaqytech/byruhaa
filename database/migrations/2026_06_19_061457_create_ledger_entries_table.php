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
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ledger_transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ledger_account_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('debit_baisa')->default(0);
            $table->unsignedInteger('credit_baisa')->default(0);
            $table->string('currency', 3)->default('OMR');
            $table->string('memo')->nullable();
            $table->timestamps();

            $table->index(['ledger_account_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
