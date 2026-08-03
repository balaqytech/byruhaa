<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\BlogPost;
use App\Modules\Events\Models\Event;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $staticUrls = collect([
            ['location' => route('home'), 'changeFrequency' => 'weekly', 'priority' => '1.0'],
            ['location' => route('events.index'), 'changeFrequency' => 'daily', 'priority' => '0.9'],
            ['location' => route('blog.index'), 'changeFrequency' => 'weekly', 'priority' => '0.7'],
            ['location' => route('about'), 'changeFrequency' => 'monthly', 'priority' => '0.6'],
            ['location' => route('contact'), 'changeFrequency' => 'monthly', 'priority' => '0.5'],
            ['location' => route('coffee'), 'changeFrequency' => 'monthly', 'priority' => '0.5'],
        ]);

        $eventUrls = Event::query()
            ->where('status', EventStatus::Published)
            ->select(['id', 'slug', 'updated_at'])
            ->get()
            ->map(fn (Event $event): array => [
                'location' => route('events.show', $event),
                'lastModified' => $event->updated_at?->toAtomString(),
                'changeFrequency' => 'weekly',
                'priority' => '0.8',
            ]);

        $postUrls = BlogPost::query()
            ->publiclyVisible()
            ->select(['id', 'slug', 'updated_at'])
            ->get()
            ->map(fn (BlogPost $post): array => [
                'location' => route('blog.show', $post),
                'lastModified' => $post->updated_at?->toAtomString(),
                'changeFrequency' => 'monthly',
                'priority' => '0.6',
            ]);

        return response()
            ->view('sitemap', ['urls' => $staticUrls->concat($eventUrls)->concat($postUrls)])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
