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
        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('birth_date');
            $table->string('school_name')->nullable();
            $table->string('grade')->nullable();
            $table->text('medical_notes')->nullable();
            $table->string('relationship_to_customer')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'birth_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_members');
    }
};
