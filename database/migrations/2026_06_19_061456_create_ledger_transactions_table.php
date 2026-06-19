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
        Schema::create('ledger_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->nullableMorphs('source');
            $table->string('description');
            $table->timestamp('occurred_at')->index();
            $table->string('currency', 3)->default('OMR');
            $table->unsignedInteger('total_baisa');
            $table->timestamps();

            $table->unique(['source_type', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_transactions');
    }
};
