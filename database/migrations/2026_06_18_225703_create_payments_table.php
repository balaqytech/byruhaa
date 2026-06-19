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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_installment_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('thawani')->index();
            $table->string('reference')->unique();
            $table->unsignedInteger('amount_baisa');
            $table->string('currency', 3)->default('OMR');
            $table->string('state')->default('pending')->index();
            $table->string('provider_session_id')->nullable()->unique();
            $table->string('provider_invoice')->nullable();
            $table->string('provider_payment_status')->nullable();
            $table->text('checkout_url')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['booking_installment_id', 'state']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
