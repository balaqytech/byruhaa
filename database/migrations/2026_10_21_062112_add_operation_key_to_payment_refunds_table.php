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
            $table->string('operation_key', 190)->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_refunds', function (Blueprint $table) {
            $table->dropUnique(['operation_key']);
            $table->dropColumn('operation_key');
        });
    }
};
