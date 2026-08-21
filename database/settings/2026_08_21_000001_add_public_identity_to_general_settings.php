<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.public_name', 'بِيرُحاء إبراء');
        $this->migrator->add('general.commercial_registration_number', '1220553');
        $this->migrator->add('general.tax_number', null);
    }
};
