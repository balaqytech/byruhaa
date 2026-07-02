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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type')->index();
            $table->bigInteger('amount_baisa')->nullable();
            $table->unsignedInteger('percentage_basis_points')->nullable();
            $table->string('currency', 3)->default('OMR');
            $table->dateTime('expires_at')->index();
            $table->unsignedSmallInteger('minimum_family_members')->default(1);
            $table->unsignedSmallInteger('maximum_family_members')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['event_id', 'is_active']);
            $table->index(['event_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
