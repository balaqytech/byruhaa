<?php

namespace App\Modules\Store\Settings;

use Spatie\LaravelSettings\Settings;

class StoreSettings extends Settings
{
    public int $vat_rate_percentage = 5;

    public ?string $legal_name = null;

    public ?string $tax_number = null;

    public ?string $receipt_address = null;

    public ?string $receipt_phone = null;

    public ?string $receipt_footer = null;

    public ?string $pickup_instructions = null;

    public ?string $opening_time = null;

    public ?string $closing_time = null;

    public int $reservation_duration_minutes = 2;

    public bool $ordering_enabled = false;

    public static function group(): string
    {
        return 'store';
    }
}
