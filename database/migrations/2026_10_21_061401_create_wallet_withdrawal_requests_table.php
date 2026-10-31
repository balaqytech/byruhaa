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
        Schema::create('wallet_withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->restrictOnDelete();
            $table->foreignId('guardian_customer_id')->constrained('customers')->restrictOnDelete();
            $table->string('reference', 32)->unique();
            $table->string('operation_key', 100)->unique();
            $table->unsignedBigInteger('amount_baisa');
            $table->char('currency', 3)->default('OMR');
            $table->string('status', 32)->default('pending');
            $table->string('method', 16)->nullable();
            $table->text('bank_account')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_beneficiary_name')->nullable();
            $table->string('payout_reference')->nullable()->unique();
            $table->string('evidence_path')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('paid_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['wallet_id', 'status']);
        });

        Schema::create('wallet_withdrawal_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_withdrawal_request_id')->constrained('wallet_withdrawal_requests', indexName: 'ww_alloc_request_fk')->restrictOnDelete();
            $table->foreignId('wallet_top_up_id')->constrained('wallet_top_ups')->restrictOnDelete();
            $table->unsignedBigInteger('amount_baisa');
            $table->timestamps();
            $table->unique(['wallet_withdrawal_request_id', 'wallet_top_up_id'], 'wallet_withdrawal_allocation_unique');
        });

        Schema::table('wallet_top_ups', function (Blueprint $table): void {
            $table->unsignedBigInteger('reserved_withdrawal_baisa')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_top_ups', function (Blueprint $table): void {
            $table->dropColumn('reserved_withdrawal_baisa');
        });
        Schema::dropIfExists('wallet_withdrawal_allocations');
        Schema::dropIfExists('wallet_withdrawal_requests');
    }
};
