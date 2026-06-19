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
        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->unsignedInteger('amount_baisa');
            $table->string('currency', 3)->default('OMR');
            $table->string('state')->default('pending')->index();
            $table->string('provider_refund_id')->nullable()->unique();
            $table->string('provider_payment_id')->nullable()->index();
            $table->string('provider_status')->nullable();
            $table->string('reason');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'state']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_refunds');
    }
};
