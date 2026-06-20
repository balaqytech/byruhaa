<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('discounts', 'currency')) {
            DB::statement(<<<'SQL'
                UPDATE discounts
                SET currency = COALESCE(
                    (SELECT events.currency FROM events WHERE events.id = discounts.event_id),
                    currency,
                    'OMR'
                )
                WHERE event_id IS NOT NULL
            SQL);
        }

        if (Schema::hasColumn('booking_installments', 'currency')) {
            DB::statement(<<<'SQL'
                UPDATE booking_installments
                SET currency = COALESCE(
                    (
                        SELECT booking_payment_schedules.currency
                        FROM booking_payment_schedules
                        WHERE booking_payment_schedules.id = booking_installments.booking_payment_schedule_id
                    ),
                    currency,
                    'OMR'
                )
            SQL);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
