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
        Schema::table('payment_refunds', function (Blueprint $table) {
            $table->string('resolution_method')->nullable()->index()->after('state');
            $table->string('manual_reference')->nullable()->unique()->after('provider_status');
            $table->text('manual_notes')->nullable()->after('manual_reference');
            $table->string('manual_evidence_path')->nullable()->after('manual_notes');
            $table->timestamp('manual_required_at')->nullable()->after('processed_at');
            $table->timestamp('manually_completed_at')->nullable()->after('manual_required_at');
            $table->foreignId('manually_completed_by_user_id')->nullable()->after('manually_completed_at')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_refunds', function (Blueprint $table) {
            $table->dropForeign(['manually_completed_by_user_id']);
            $table->dropColumn([
                'resolution_method',
                'manual_reference',
                'manual_notes',
                'manual_evidence_path',
                'manual_required_at',
                'manually_completed_at',
                'manually_completed_by_user_id',
            ]);
        });
    }
};
