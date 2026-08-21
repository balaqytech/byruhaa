<?php

namespace Database\Factories;

use App\Modules\Content\Enums\PublicPageStatus;
use App\Modules\Content\Models\PublicPage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PublicPage> */
class PublicPageFactory extends Factory
{
    protected $model = PublicPage::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'key' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 9999),
            'title' => $title,
            'content' => '<p>'.fake()->paragraph().'</p>',
            'status' => PublicPageStatus::Draft,
            'published_at' => null,
            'effective_at' => null,
            'version' => 1,
            'meta_title' => null,
            'meta_description' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => PublicPageStatus::Published,
            'published_at' => now()->subMinute(),
        ]);
    }
}
