<?php

use App\Enums\BlogPostStatus;
use App\Models\BlogPost;
use App\Models\BlogPostCategory;
use Illuminate\Database\QueryException;

test('blog post can belong to a category', function () {
    $category = BlogPostCategory::factory()->create();
    $post = BlogPost::factory()->for($category, 'category')->create();

    expect($post->category)
        ->toBeInstanceOf(BlogPostCategory::class)
        ->id->toBe($category->id);
});

test('published scope only returns published posts that are not scheduled', function () {
    $published = BlogPost::factory()->published()->create();

    BlogPost::factory()->create([
        'status' => BlogPostStatus::Draft,
        'published_at' => now()->subDay(),
    ]);

    BlogPost::factory()->scheduled()->create();

    expect(BlogPost::published()->pluck('id')->all())
        ->toBe([$published->id]);
});

test('blog slugs are unique', function () {
    BlogPostCategory::factory()->create(['slug' => 'news']);
    BlogPost::factory()->create(['slug' => 'first-post']);

    expect(fn () => BlogPostCategory::factory()->create(['slug' => 'news']))
        ->toThrow(QueryException::class);

    expect(fn () => BlogPost::factory()->create(['slug' => 'first-post']))
        ->toThrow(QueryException::class);
});

test('category visible scope only returns visible categories', function () {
    $visible = BlogPostCategory::factory()->create(['is_visible' => true]);

    BlogPostCategory::factory()->hidden()->create();

    expect(BlogPostCategory::visible()->pluck('id')->all())
        ->toBe([$visible->id]);
});
