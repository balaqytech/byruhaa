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
        Schema::table('events', function (Blueprint $table) {
            $table->bigInteger('price_baisa')->default(0)->change();
        });

        Schema::table('discounts', function (Blueprint $table) {
            $table->bigInteger('amount_baisa')->change();
            $table->string('currency', 3)->default('OMR');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->bigInteger('unit_price_baisa')->default(0)->change();
            $table->bigInteger('subtotal_baisa')->default(0)->change();
            $table->bigInteger('discount_amount_baisa')->default(0)->change();
            $table->bigInteger('total_baisa')->default(0)->change();
        });

        Schema::table('booking_payment_schedules', function (Blueprint $table) {
            $table->bigInteger('subtotal_baisa')->default(0)->change();
            $table->bigInteger('discount_amount_baisa')->default(0)->change();
            $table->bigInteger('total_baisa')->default(0)->change();
        });

        Schema::table('booking_installments', function (Blueprint $table) {
            $table->bigInteger('gross_amount_baisa')->default(0)->change();
            $table->bigInteger('discount_amount_baisa')->default(0)->change();
            $table->bigInteger('amount_baisa')->default(0)->change();
            $table->string('currency', 3)->default('OMR');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->bigInteger('amount_baisa')->change();
        });

        Schema::table('ledger_transactions', function (Blueprint $table) {
            $table->bigInteger('total_baisa')->change();
        });

        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->bigInteger('debit_baisa')->default(0)->change();
            $table->bigInteger('credit_baisa')->default(0)->change();
        });

        Schema::table('payment_refunds', function (Blueprint $table) {
            $table->bigInteger('amount_baisa')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_refunds', function (Blueprint $table) {
            $table->unsignedInteger('amount_baisa')->change();
        });

        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->unsignedInteger('debit_baisa')->default(0)->change();
            $table->unsignedInteger('credit_baisa')->default(0)->change();
        });

        Schema::table('ledger_transactions', function (Blueprint $table) {
            $table->unsignedInteger('total_baisa')->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedInteger('amount_baisa')->change();
        });

        Schema::table('booking_installments', function (Blueprint $table) {
            $table->dropColumn('currency');
            $table->unsignedInteger('gross_amount_baisa')->default(0)->change();
            $table->unsignedInteger('discount_amount_baisa')->default(0)->change();
            $table->unsignedInteger('amount_baisa')->default(0)->change();
        });

        Schema::table('booking_payment_schedules', function (Blueprint $table) {
            $table->unsignedInteger('subtotal_baisa')->default(0)->change();
            $table->unsignedInteger('discount_amount_baisa')->default(0)->change();
            $table->unsignedInteger('total_baisa')->default(0)->change();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedInteger('unit_price_baisa')->default(0)->change();
            $table->unsignedInteger('subtotal_baisa')->default(0)->change();
            $table->unsignedInteger('discount_amount_baisa')->default(0)->change();
            $table->unsignedInteger('total_baisa')->default(0)->change();
        });

        Schema::table('discounts', function (Blueprint $table) {
            $table->dropColumn('currency');
            $table->unsignedInteger('amount_baisa')->change();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->unsignedInteger('price_baisa')->default(0)->change();
        });
    }
};
