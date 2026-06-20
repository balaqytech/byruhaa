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
        Schema::create('event_payment_plan_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_payment_plan_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->unsignedSmallInteger('sequence');
            $table->unsignedTinyInteger('percentage');
            $table->date('due_date');
            $table->timestamps();

            $table->unique(['event_payment_plan_id', 'sequence'], 'ev_pp_inst_planid_seq_uq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_payment_plan_installments');
    }
};
