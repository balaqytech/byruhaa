<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class AboutPageSettings extends Settings
{
    public string $eyebrow;

    public string $page_title;

    public string $hero_summary;

    public string $intro_heading;

    public string $intro_body;

    /** @phpstan-var array<int, array<string, string>> */
    public array $highlights;

    public string $facilities_heading;

    /** @phpstan-var array<int, array<string, string>> */
    public array $facilities;

    public string $advantages_heading;

    /** @phpstan-var array<int, array<string, string>> */
    public array $advantages;

    public ?string $hero_image_id;

    /** @phpstan-var array<int, string> */
    public array $gallery_image_ids;

    public string $gallery_heading;

    public string $gallery_intro;

    public string $meta_title;

    public string $meta_description;

    public static function group(): string
    {
        return 'about-page';
    }
}
