<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public bool $coming_soon_enabled;

    public static function group(): string
    {
        return 'general';
    }
}
