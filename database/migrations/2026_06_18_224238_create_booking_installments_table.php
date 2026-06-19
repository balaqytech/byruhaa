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
        Schema::create('booking_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_payment_schedule_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->unsignedSmallInteger('sequence');
            $table->unsignedTinyInteger('percentage');
            $table->date('due_date')->index();
            $table->unsignedInteger('gross_amount_baisa')->default(0);
            $table->unsignedInteger('discount_amount_baisa')->default(0);
            $table->unsignedInteger('amount_baisa')->default(0);
            $table->string('state')->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['booking_payment_schedule_id', 'sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_installments');
    }
};
