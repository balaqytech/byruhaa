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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone_number')->unique();
            $table->string('civil_id')->nullable();
            $table->string('address')->nullable();
            $table->string('wilaya')->nullable();
            $table->string('area')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->json('additional_info')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
