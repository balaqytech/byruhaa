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
        Schema::table('store_products', function (Blueprint $table): void {
            $table->string('source_name')->nullable()->after('name');
            $table->string('author_name')->nullable()->after('source_name');
            $table->string('display_tag')->nullable()->after('author_name');
            $table->json('allergens')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_products', function (Blueprint $table): void {
            $table->dropColumn(['source_name', 'author_name', 'display_tag', 'allergens']);
        });
    }
};
