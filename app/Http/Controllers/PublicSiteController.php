<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Discount;
use App\Models\Event;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class PublicSiteController extends Controller
{
    private const HomepageEventId = 2;

    private const HomepageEventSlug = 'your-guide-to-life-after-school';

    public function home(): View
    {
        $event = $this->homepageEvent();

        return view('pages.public.site.home', [
            'event' => $event,
            'remainingSeats' => $event?->remainingSeats(),
            'title' => $event?->name ?? 'دليلك إلى الحياة بعد المدرسة',
            'metaDescription' => $event?->excerpt ?: 'ثلاثة أيام في إبراء تساعد خريج الصف الثاني عشر على اكتشاف مساره، وبناء خطة تسعين يومًا للحياة بعد المدرسة.',
        ]);
    }

    private function homepageEvent(): ?Event
    {
        return $this->homepageEventQuery()
            ->whereKey(self::HomepageEventId)
            ->where('slug', self::HomepageEventSlug)
            ->first()
            ?? $this->homepageEventQuery()
                ->where('slug', self::HomepageEventSlug)
                ->first();
    }

    /**
     * @return Builder<Event>
     */
    private function homepageEventQuery(): Builder
    {
        return Event::query()
            ->with([
                'discounts' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderByDesc('amount_baisa')
                    ->orderBy('id'),
                'paymentPlans' => fn ($query) => $query
                    ->where('is_active', true)
                    ->with('installments')
                    ->orderBy('name'),
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
