<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_products', function (Blueprint $table): void {
            $table->foreignId('video_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->json('gallery_image_ids')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('store_products', function (Blueprint $table): void {
            $table->dropForeign(['video_id']);
            $table->dropColumn(['video_id', 'gallery_image_ids']);
        });
    }
};
