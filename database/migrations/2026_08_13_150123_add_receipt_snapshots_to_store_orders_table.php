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
        Schema::table('store_orders', function (Blueprint $table): void {
            $table->unsignedTinyInteger('vat_rate_percentage')->nullable()->after('vat_baisa');
            $table->string('seller_legal_name')->nullable()->after('vat_rate_percentage');
            $table->string('seller_tax_number')->nullable()->after('seller_legal_name');
            $table->text('seller_address')->nullable()->after('seller_tax_number');
            $table->string('seller_phone')->nullable()->after('seller_address');
            $table->text('receipt_footer')->nullable()->after('seller_phone');
            $table->timestamp('paid_at')->nullable()->after('receipt_footer')->index();
            $table->string('payment_reference')->nullable()->after('paid_at');
            $table->string('provider_invoice')->nullable()->after('payment_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_orders', function (Blueprint $table): void {
            $table->dropIndex(['paid_at']);
            $table->dropColumn([
                'vat_rate_percentage',
                'seller_legal_name',
                'seller_tax_number',
                'seller_address',
                'seller_phone',
                'receipt_footer',
                'paid_at',
                'payment_reference',
                'provider_invoice',
            ]);
        });
    }
};
