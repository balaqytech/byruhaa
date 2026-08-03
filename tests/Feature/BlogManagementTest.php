<?php

use App\Enums\BlogPostStatus;
use App\Modules\Content\Models\BlogPost;
use App\Modules\Content\Models\BlogPostCategory;
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

test('public blog index only shows published posts from visible categories', function () {
    $visibleCategory = BlogPostCategory::factory()->create(['name' => 'Visible category']);
    $hiddenCategory = BlogPostCategory::factory()->hidden()->create(['name' => 'Hidden category']);
    $published = BlogPost::factory()->published()->for($visibleCategory, 'category')->create(['title' => 'Public post']);

    BlogPost::factory()->for($visibleCategory, 'category')->create(['title' => 'Draft post']);
    BlogPost::factory()->scheduled()->for($visibleCategory, 'category')->create(['title' => 'Future post']);
    BlogPost::factory()->published()->for($hiddenCategory, 'category')->create(['title' => 'Hidden category post']);

    $this->get(route('blog.index'))
        ->assertSuccessful()
        ->assertSee($published->title)
        ->assertSee($visibleCategory->name)
        ->assertDontSee('Draft post')
        ->assertDontSee('Future post')
        ->assertDontSee('Hidden category post')
        ->assertDontSee($hiddenCategory->name);
});

test('public blog detail only shows publicly visible posts', function () {
    $visibleCategory = BlogPostCategory::factory()->create();
    $hiddenCategory = BlogPostCategory::factory()->hidden()->create();
    $published = BlogPost::factory()->published()->for($visibleCategory, 'category')->create();
    $draft = BlogPost::factory()->for($visibleCategory, 'category')->create();
    $scheduled = BlogPost::factory()->scheduled()->for($visibleCategory, 'category')->create();
    $hiddenCategoryPost = BlogPost::factory()->published()->for($hiddenCategory, 'category')->create();

    $this->get(route('blog.show', $published))->assertSuccessful();
    $this->get(route('blog.show', $draft))->assertNotFound();
    $this->get(route('blog.show', $scheduled))->assertNotFound();
    $this->get(route('blog.show', $hiddenCategoryPost))->assertNotFound();
});

test('public category page only shows visible categories and their published posts', function () {
    $category = BlogPostCategory::factory()->create(['name' => 'Visible category']);
    $hiddenCategory = BlogPostCategory::factory()->hidden()->create();
    $post = BlogPost::factory()->published()->for($category, 'category')->create(['title' => 'Category post']);

    BlogPost::factory()->published()->create(['title' => 'Other post']);
    BlogPost::factory()->for($category, 'category')->create(['title' => 'Draft category post']);

    $this->get(route('blog.category', $category))
        ->assertSuccessful()
        ->assertSee($category->name)
        ->assertSee($post->title)
        ->assertDontSee('Other post')
        ->assertDontSee('Draft category post');

    $this->get(route('blog.category', $hiddenCategory))->assertNotFound();
});

test('social share image falls back to featured image path', function () {
    $post = BlogPost::factory()->make([
        'featured_image_path' => 'storage/blog/featured.jpg',
        'social_share_image_id' => null,
    ]);

    expect($post->socialShareImageUrl())->toBe(asset('storage/blog/featured.jpg'));
});
