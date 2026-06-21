<?php

use App\Enums\BlogPostStatus;
use App\Enums\EventStatus;
use App\Models\BlogPost;
use App\Models\BlogPostCategory;
use App\Models\Event;

test('homepage loads', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('منتجع بيرحاء')
        ->assertSee('نصنع تجارب سياحية وتعليمية بروح عُمانية فاخرة')
        ->assertSee('الموقع قيد التجهيز')
        ->assertSee('حسابي');
});

test('events page loads', function () {
    $this->get(route('events.index'))
        ->assertSuccessful()
        ->assertSee('الفعاليات');
});

test('blog page loads', function () {
    $this->get(route('blog.index'))
        ->assertSuccessful()
        ->assertSee(__('ui.blog.heading'));
});

test('about page loads', function () {
    $this->get(route('about'))
        ->assertSuccessful()
        ->assertSee('صفحة عن المنتجع قيد التجهيز');
});

test('contact page loads', function () {
    $this->get(route('contact'))
        ->assertSuccessful()
        ->assertSee('صفحة التواصل قيد التجهيز');
});

test('events page only shows published events', function () {
    $published = Event::factory()->create([
        'name' => 'Published desert retreat',
        'status' => EventStatus::Published,
    ]);

    Event::factory()->create([
        'name' => 'Draft hidden retreat',
        'status' => EventStatus::Draft,
    ]);

    $this->get(route('events.index'))
        ->assertSuccessful()
        ->assertSee($published->name)
        ->assertDontSee('Draft hidden retreat');
});

test('blog page only shows published posts', function () {
    $category = BlogPostCategory::factory()->create(['name' => 'Visible category']);
    $published = BlogPost::factory()->published()->for($category, 'category')->create([
        'title' => 'Published public story',
    ]);

    BlogPost::factory()->for($category, 'category')->create([
        'title' => 'Draft hidden story',
        'status' => BlogPostStatus::Draft,
    ]);

    BlogPost::factory()->scheduled()->for($category, 'category')->create([
        'title' => 'Scheduled hidden story',
    ]);

    $this->get(route('blog.index'))
        ->assertSuccessful()
        ->assertSee($published->title)
        ->assertDontSee('Draft hidden story')
        ->assertDontSee('Scheduled hidden story');
});
