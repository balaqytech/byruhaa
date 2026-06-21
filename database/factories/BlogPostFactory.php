<?php

namespace Database\Factories;

use App\Enums\BlogPostStatus;
use App\Models\BlogPost;
use App\Models\BlogPostCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BlogPost>
 */
class BlogPostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'blog_post_category_id' => BlogPostCategory::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(5)),
            'excerpt' => fake()->optional()->paragraph(),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'featured_image_path' => null,
            'featured_image_id' => null,
            'status' => BlogPostStatus::Draft,
            'published_at' => null,
            'meta_title' => fake()->optional()->sentence(4),
            'meta_description' => fake()->optional()->paragraph(),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BlogPostStatus::Published,
            'published_at' => now()->subMinute(),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BlogPostStatus::Published,
            'published_at' => now()->addDay(),
        ]);
    }
}
