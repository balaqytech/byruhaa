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
        Schema::table('webhook_deliveries', function (Blueprint $table): void {
            $table->string('delivery_key', 120)->default('')->after('event');
            $table->dropUnique('webhook_deliveries_unique_delivery');
            $table->unique(
                ['event', 'webhookable_type', 'webhookable_id', 'webhook_url_hash', 'delivery_key'],
                'webhook_deliveries_unique_delivery',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webhook_deliveries', function (Blueprint $table): void {
            $table->dropUnique('webhook_deliveries_unique_delivery');
            $table->unique(
                ['event', 'webhookable_type', 'webhookable_id', 'webhook_url_hash'],
                'webhook_deliveries_unique_delivery',
            );
            $table->dropColumn('delivery_key');
        });
    }
};
