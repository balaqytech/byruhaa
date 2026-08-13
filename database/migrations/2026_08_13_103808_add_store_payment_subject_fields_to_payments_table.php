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
        Schema::table('payments', function (Blueprint $table): void {
            $table->foreignId('booking_installment_id')->nullable()->change();
            $table->string('subject_type')->nullable()->after('booking_installment_id');
            $table->string('subject_reference')->nullable()->after('subject_type');
            $table->index(['subject_type', 'subject_reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex(['subject_type', 'subject_reference']);
            $table->dropColumn(['subject_type', 'subject_reference']);
            $table->foreignId('booking_installment_id')->nullable(false)->change();
        });
    }
};
