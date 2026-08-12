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
        Schema::create('event_cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->index();
            $table->text('reason');
            $table->string('currency', 3);
            $table->unsignedInteger('bookings_count')->default(0);
            $table->unsignedInteger('payments_count')->default(0);
            $table->unsignedBigInteger('refundable_amount_baisa')->default(0);
            $table->unsignedInteger('refunded_payments_count')->default(0);
            $table->unsignedBigInteger('refunded_amount_baisa')->default(0);
            $table->json('errors')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_cancellations');
    }
};
