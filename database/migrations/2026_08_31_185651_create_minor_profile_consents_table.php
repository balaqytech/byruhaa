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
        Schema::create('minor_profile_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('minor_profile_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 64);
            $table->string('policy_version', 64);
            $table->string('policy_hash', 128);
            $table->timestamp('accepted_at');
            $table->ipAddress('accepted_ip')->nullable();
            $table->timestamps();

            $table->index(['minor_profile_id', 'purpose']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('minor_profile_consents');
    }
};
