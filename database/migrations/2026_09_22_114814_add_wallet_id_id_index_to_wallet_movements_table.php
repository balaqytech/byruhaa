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
        Schema::table('wallet_movements', function (Blueprint $table) {
            $table->index(['wallet_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_movements', function (Blueprint $table) {
            $table->dropIndex(['wallet_id', 'id']);
        });
    }
};
