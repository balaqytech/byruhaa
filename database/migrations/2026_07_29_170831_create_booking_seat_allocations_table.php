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
        Schema::create('booking_seat_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_price_tier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('seat_count');
            $table->string('state')->default('held');
            $table->string('tier_name')->nullable();
            $table->unsignedBigInteger('tier_unit_price_baisa')->nullable();
            $table->timestamp('held_at')->nullable();
            $table->timestamp('hold_expires_at')->nullable();
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'state']);
            $table->index(['event_price_tier_id', 'state']);
            $table->index(['state', 'hold_expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_seat_allocations');
    }
};
