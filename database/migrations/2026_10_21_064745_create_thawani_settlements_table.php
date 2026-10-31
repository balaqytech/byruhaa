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
        Schema::create('thawani_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('provider_reference')->unique();
            $table->bigInteger('gross_baisa');
            $table->unsignedBigInteger('fee_baisa');
            $table->unsignedBigInteger('bank_net_baisa');
            $table->char('currency', 3)->default('OMR');
            $table->timestamp('settled_at');
            $table->string('statement_path');
            $table->foreignId('recorded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('thawani_settlement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thawani_settlement_id')->constrained()->restrictOnDelete();
            $table->string('provider_operation_reference')->unique();
            $table->string('kind', 16);
            $table->bigInteger('amount_baisa');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thawani_settlement_items');
        Schema::dropIfExists('thawani_settlements');
    }
};
