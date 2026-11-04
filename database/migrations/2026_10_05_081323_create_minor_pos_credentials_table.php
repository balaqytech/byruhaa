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
        Schema::create('minor_pos_credentials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('minor_profile_id')->unique()->constrained('minor_profiles')->cascadeOnDelete();
            $table->char('token_hash', 64)->nullable()->unique();
            $table->string('pin_hash')->nullable();
            $table->unsignedTinyInteger('failed_pin_attempts')->default(0);
            $table->timestamp('pin_locked_until')->nullable();
            $table->timestamp('card_issued_at')->nullable();
            $table->timestamp('card_revoked_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('minor_pos_credentials');
    }
};
