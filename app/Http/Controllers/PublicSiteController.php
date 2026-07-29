<?php

namespace App\Http\Controllers;

use App\Actions\RenderEventLandingPage;
use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Contracts\View\View;

class PublicSiteController extends Controller
{
    public function __construct(
        private readonly RenderEventLandingPage $renderEventLandingPage,
    ) {}

    public function home(): View
    {
        $events = Event::query()
            ->where('status', EventStatus::Published)
            ->orderByRaw('starts_at IS NULL')
            ->orderBy('starts_at')
            ->orderByDesc('id')
            ->limit(4)
            ->get();

        return view('pages.public.site.home', [
            'featuredEvent' => $events->first(),
            'upcomingEvents' => $events->skip(1),
            'title' => 'بيرحاء، برامج تربوية تصنع أثرًا',
            'metaDescription' => 'برامج ومخيمات ورحلات تربوية للفتيان تجمع العبادة والعلم والعمل والصحبة في تجارب عملية ممتدة الأثر.',
        ]);
    }

    public function events(): View
    {
        return view('pages.public.site.events.index', [
            'events' => Event::query()
                ->where('status', EventStatus::Published)
                ->orderBy('starts_at')
                ->get(),
            'title' => 'الفعاليات',
            'metaDescription' => 'فعاليات وتجارب منتجع بيرحاء السياحية والتعليمية.',
        ]);
    }

    public function event(Event $event): View
    {
        abort_unless($event->status === EventStatus::Published, 404);

        return $this->renderEventLandingPage->handle($event);
    }

    public function about(): View
    {
        return view('pages.public.site.about', [
            'title' => 'عن المنتجع',
            'metaDescription' => 'صفحة عن منتجع بيرحاء قيد التجهيز.',
        ]);
    }

    public function contact(): View
    {
        return view('pages.public.site.contact', [
            'title' => 'تواصل معنا',
            'metaDescription' => 'صفحة التواصل مع منتجع بيرحاء قيد التجهيز.',
        ]);
    }
}
