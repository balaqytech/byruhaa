<?php

use App\Models\BlogPost;
use App\Modules\Events\Models\Event;

test('sitemap contains public pages published events and visible blog posts', function () {
    $event = Event::factory()->create();
    $draftEvent = Event::factory()->draft()->create();
    $post = BlogPost::factory()->published()->create();
    $draftPost = BlogPost::factory()->create();

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('<?xml version="1.0" encoding="UTF-8"?>', false)
        ->assertSee(route('home'), false)
        ->assertSee(route('events.show', $event), false)
        ->assertSee(route('blog.show', $post), false)
        ->assertDontSee(route('events.show', $draftEvent), false)
        ->assertDontSee(route('blog.show', $draftPost), false);
});
