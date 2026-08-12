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
        Schema::create('affiliate_commission_reversals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_commission_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('event_cancellation_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount_baisa');
            $table->string('currency', 3);
            $table->text('reason');
            $table->timestamp('reversed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affiliate_commission_reversals');
    }
};
