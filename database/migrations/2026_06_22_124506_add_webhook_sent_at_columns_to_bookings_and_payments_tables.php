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
        Schema::table('bookings', function (Blueprint $table) {
            $table->timestamp('booking_approved_webhook_sent_at')->nullable()->after('reviewed_at');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('payment_paid_webhook_sent_at')->nullable()->after('paid_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('booking_approved_webhook_sent_at');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('payment_paid_webhook_sent_at');
        });
    }
};
