<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Discount;
use App\Models\Event;
use Illuminate\Contracts\View\View;

class PublicSiteController extends Controller
{
    public function home(): View
    {
        return view('pages.public.site.home', [
            'title' => 'منتجع بيرحاء',
            'metaDescription' => 'نصنع تجارب سياحية وتعليمية بروح عُمانية فاخرة',
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

        $event->load([
            'paymentPlans' => fn ($query) => $query
                ->where('is_active', true)
                ->with('installments')
                ->orderBy('name'),
        ]);

        return view('pages.public.site.events.show', [
            'event' => $event,
            'availableDiscounts' => Discount::query()
                ->availableForEvent($event)
                ->orderByDesc('amount_baisa')
                ->orderBy('id')
                ->get(),
            'title' => $event->name,
            'metaDescription' => $event->excerpt ?: 'Event details for '.$event->name,
        ]);
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
