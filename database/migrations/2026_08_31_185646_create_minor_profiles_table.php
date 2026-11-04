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
        Schema::create('minor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_member_id')->unique()->constrained()->restrictOnDelete();
            $table->string('member_code', 32)->unique();
            $table->string('password');
            $table->string('status', 64)->index();
            $table->boolean('direct_payment_enabled')->default(false);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('invalidated_at')->nullable();
            $table->text('invalidation_reason')->nullable();
            $table->timestamp('deletion_requested_at')->nullable();
            $table->string('activation_token_hash')->nullable();
            $table->timestamp('activation_token_expires_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('minor_profiles');
    }
};
