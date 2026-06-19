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
        Schema::create('event_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_family_member_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('state')->default('awaiting_signature')->index();
            $table->longText('contract_html');
            $table->json('participant_extra_answers')->nullable();
            $table->timestamp('participant_extra_completed_at')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('signed_name')->nullable();
            $table->string('signed_ip')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_contracts');
    }
};
