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
        Schema::create('thawani_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('received')->index();
            $table->string('event_type')->nullable()->index();
            $table->string('provider_event_id')->nullable()->index();
            $table->string('client_reference_id')->nullable()->index();
            $table->string('provider_session_id')->nullable()->index();
            $table->string('provider_payment_id')->nullable()->index();
            $table->string('provider_invoice')->nullable()->index();
            $table->char('payload_hash', 64)->index();
            $table->json('request_headers')->nullable();
            $table->json('payload');
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thawani_webhook_events');
    }
};
