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
        Schema::create('financial_reconciliation_runs', function (Blueprint $table) {
            $table->id();
            $table->date('as_of_date');
            $table->string('bank_statement_path')->nullable();
            $table->string('status', 24);
            $table->unsignedInteger('discrepancy_count')->default(0);
            $table->unsignedInteger('pending_count')->default(0);
            $table->json('issues');
            $table->foreignId('run_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_reconciliation_runs');
    }
};
