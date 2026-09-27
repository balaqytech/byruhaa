<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_products', function (Blueprint $table): void {
            $table->longText('long_description')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('store_products', function (Blueprint $table): void {
            $table->dropColumn('long_description');
        });
    }
};
