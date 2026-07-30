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
        Schema::create('event_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('interested');
            $table->string('source')->default('website');
            $table->string('preferred_contact_channel')->nullable();
            $table->string('source_reference')->nullable();
            $table->timestamp('contact_consent_at')->nullable();
            $table->timestamp('last_expressed_at');
            $table->timestamp('converted_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();
            $table->unique(['customer_id', 'event_id']);
            $table->index(['event_id', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_interests');
    }
};
