<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_carts', function (Blueprint $table): void {
            $table->char('uchat_owner_key', 64)->nullable()->after('customer_id');
            $table->unique('uchat_owner_key');
        });
    }

    public function down(): void
    {
        Schema::table('store_carts', function (Blueprint $table): void {
            $table->dropUnique(['uchat_owner_key']);
            $table->dropColumn('uchat_owner_key');
        });
    }
};
