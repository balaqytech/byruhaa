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
        Schema::create('cash_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cashier_user_id')->constrained('users')->restrictOnDelete();
            $table->date('business_date');
            $table->unsignedBigInteger('opening_float_baisa');
            $table->unsignedBigInteger('receipts_baisa');
            $table->unsignedBigInteger('refunds_baisa');
            $table->unsignedBigInteger('withdrawals_baisa');
            $table->bigInteger('expected_baisa');
            $table->unsignedBigInteger('counted_baisa');
            $table->bigInteger('difference_baisa');
            $table->string('status', 24);
            $table->json('issues')->nullable();
            $table->foreignId('closed_by_user_id')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['cashier_user_id', 'business_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_closures');
    }
};
