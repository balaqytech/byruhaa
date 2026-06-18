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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('state')->default('pending_review')->index();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->unsignedInteger('unit_price_baisa')->default(0);
            $table->string('currency', 3)->default('OMR');
            $table->unsignedSmallInteger('family_member_count')->default(0);
            $table->unsignedInteger('subtotal_baisa')->default(0);
            $table->foreignId('discount_id')->nullable()->constrained()->nullOnDelete();
            $table->string('discount_name')->nullable();
            $table->unsignedInteger('discount_amount_baisa')->default(0);
            $table->unsignedInteger('total_baisa')->default(0);
            $table->timestamps();

            $table->index(['customer_id', 'state']);
            $table->index(['event_id', 'state']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
