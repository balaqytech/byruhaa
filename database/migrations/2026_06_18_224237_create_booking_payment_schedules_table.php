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
        Schema::create('booking_payment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('event_payment_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('plan_name');
            $table->string('currency', 3)->default('OMR');
            $table->unsignedInteger('subtotal_baisa')->default(0);
            $table->unsignedInteger('discount_amount_baisa')->default(0);
            $table->unsignedInteger('total_baisa')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_payment_schedules');
    }
};
