<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class ContactPageSettings extends Settings
{
    public string $eyebrow;

    public string $page_title;

    public string $intro;

    public string $assistant_title;

    public string $assistant_description;

    public string $assistant_button_label;

    public string $assistant_url;

    public string $phone;

    public ?string $email;

    public string $location;

    public string $visiting_hours;

    public ?string $map_url;

    public string $social_heading;

    public string $social_intro;

    public array $social_links;

    public string $meta_title;

    public string $meta_description;

    public static function group(): string
    {
        return 'contact-page';
    }
}
