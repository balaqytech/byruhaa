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
        if (! Schema::hasColumn('event_interest_leads', 'student_name')) {
            Schema::table('event_interest_leads', function (Blueprint $table) {
                $table->string('student_name')->nullable()->after('guardian_name');
            });
        }

        Schema::table('event_interest_leads', function (Blueprint $table) {
            $table->unique(['event_id', 'whatsapp_number', 'student_name'], 'event_interest_leads_event_phone_student_unique');
        });

        Schema::table('event_interest_leads', function (Blueprint $table) {
            $table->dropUnique('event_interest_leads_event_id_whatsapp_number_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_interest_leads', function (Blueprint $table) {
            $table->unique(['event_id', 'whatsapp_number']);
        });

        Schema::table('event_interest_leads', function (Blueprint $table) {
            $table->dropUnique('event_interest_leads_event_phone_student_unique');
            $table->dropColumn('student_name');
        });
    }
};
