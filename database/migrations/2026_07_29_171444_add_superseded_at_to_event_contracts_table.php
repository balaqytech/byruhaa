<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('event_contracts', function (Blueprint $table) {
            $table->index('booking_family_member_id', 'event_contracts_booking_family_member_lookup_index');
        });

        Schema::table('event_contracts', function (Blueprint $table) {
            $table->dropUnique(['booking_family_member_id']);
        });

        Schema::table('event_contracts', function (Blueprint $table) {
            $table->timestamp('superseded_at')->nullable()->after('signed_at');
            $table->index(['booking_family_member_id', 'superseded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('event_contracts')->whereNotNull('superseded_at')->delete();

        Schema::table('event_contracts', function (Blueprint $table) {
            $table->dropIndex(['booking_family_member_id', 'superseded_at']);
            $table->dropColumn('superseded_at');
        });

        Schema::table('event_contracts', function (Blueprint $table) {
            $table->unique('booking_family_member_id');
        });

        Schema::table('event_contracts', function (Blueprint $table) {
            $table->dropIndex('event_contracts_booking_family_member_lookup_index');
        });
    }
};
