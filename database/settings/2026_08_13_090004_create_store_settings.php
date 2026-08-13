<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('store.vat_rate_percentage', 5);
        $this->migrator->add('store.legal_name', null);
        $this->migrator->add('store.tax_number', null);
        $this->migrator->add('store.receipt_address', null);
        $this->migrator->add('store.receipt_phone', null);
        $this->migrator->add('store.receipt_footer', null);
        $this->migrator->add('store.pickup_instructions', null);
        $this->migrator->add('store.opening_time', null);
        $this->migrator->add('store.closing_time', null);
        $this->migrator->add('store.reservation_duration_minutes', 2);
        $this->migrator->add('store.ordering_enabled', false);
    }
};
