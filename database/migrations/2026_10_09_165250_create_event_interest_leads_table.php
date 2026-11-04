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
        Schema::create('event_interest_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('reference_code', 32)->unique();
            $table->string('guardian_name');
            $table->string('whatsapp_number', 20);
            $table->string('wilaya');
            $table->string('student_grade');
            $table->string('recitation_level')->nullable();
            $table->string('preferred_track')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('consent_at');
            $table->timestamps();

            $table->unique(['event_id', 'whatsapp_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_interest_leads');
    }
};
