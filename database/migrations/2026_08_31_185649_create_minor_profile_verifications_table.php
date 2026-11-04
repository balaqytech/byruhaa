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
        Schema::create('minor_profile_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('minor_profile_id')->constrained()->cascadeOnDelete();
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['minor_profile_id', 'consumed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('minor_profile_verifications');
    }
};
