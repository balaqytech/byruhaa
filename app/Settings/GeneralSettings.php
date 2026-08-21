<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public bool $coming_soon_enabled;

    public string $public_name = 'بِيرُحاء إبراء';

    public ?string $commercial_registration_number = '1220553';

    public ?string $tax_number = null;

    public static function group(): string
    {
        return 'general';
    }
}
