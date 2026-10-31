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
        Schema::create('financial_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_key', 190)->unique();
            $table->string('kind', 64);
            $table->string('subject_type', 64);
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('amount_baisa');
            $table->char('currency', 3)->default('OMR');
            $table->timestamp('occurred_at');
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_events');
    }
};
