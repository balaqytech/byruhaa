<?php

namespace App\Http\Controllers;

use App\Enums\EventEnrollmentStatus;
use App\Enums\EventStatus;
use App\Enums\SeatAllocationState;
use App\Modules\Events\Actions\BuildEventPriceTierOffer;
use App\Modules\Events\Actions\RenderEventLandingPage;
use App\Modules\Events\Models\Event;
use App\Settings\AboutPageSettings;
use App\Settings\ContactPageSettings;
use Illuminate\Contracts\View\View;
use Slimani\MediaManager\Models\File;

class PublicSiteController extends Controller
{
    public function __construct(
        private readonly RenderEventLandingPage $renderEventLandingPage,
        private readonly BuildEventPriceTierOffer $buildEventPriceTierOffer,
    ) {}

    public function home(): View
    {
        $events = Event::query()
            ->where('status', EventStatus::Published)
            ->where(fn ($query) => $query
                ->where('starts_at', '>=', now())
                ->orWhere('ends_at', '>=', now())
                ->orWhereNull('starts_at'))
            ->withSum([
                'seatAllocations as unavailable_seats_count' => fn ($query) => $query->whereIn('state', [
                    SeatAllocationState::Held->value,
                    SeatAllocationState::Reserved->value,
                ]),
            ], 'seat_count')
            ->with([
                'priceTiers' => fn ($query) => $query
                    ->where('is_active', true)
                    ->withSum([
                        'seatAllocations as unavailable_seats_count' => fn ($query) => $query->whereIn('state', [
                            SeatAllocationState::Held->value,
                            SeatAllocationState::Reserved->value,
                        ]),
                    ], 'seat_count')
                    ->orderBy('position'),
            ])
            ->orderByRaw('starts_at IS NULL')
            ->orderBy('starts_at')
            ->orderByDesc('id')
            ->limit(4)
            ->get();

        $featuredEvent = $events->first();

        return view('pages.public.site.home', [
            'featuredEvent' => $featuredEvent,
            'featuredTierOffer' => $featuredEvent === null
                ? null
                : $this->buildEventPriceTierOffer->handle($featuredEvent),
            'upcomingEvents' => $events->skip(1),
            'title' => 'بيرحاء، برامج تربوية تصنع أثرًا',
            'metaDescription' => 'برامج ومخيمات ورحلات تربوية للفتيان تجمع العبادة والعلم والعمل والصحبة في تجارب عملية ممتدة الأثر.',
        ]);
    }

    public function coffee(): View
    {
        return view('pages.public.site.coffee', [
            'coffee' => config('coffee'),
            'title' => 'قهوة بيرحاء',
            'metaDescription' => 'قهوة مختصة ومخبوزات خفيفة في مخيم بيرحاء بولاية إبراء، مع قائمة واضحة والبيع من الموقع.',
            'metaImage' => asset('images/coffee-byruha-hero.webp'),
        ]);
    }

    public function checkout(): View
    {
        return view('pages.public.site.store.checkout', [
            'title' => 'إتمام طلب القهوة',
            'metaDescription' => 'أكمل بيانات طلب القهوة واختر وقت الاستلام ثم انتقل إلى الدفع الآمن.',
        ]);
    }

    public function events(): View
    {
        $events = Event::query()
            ->where('status', EventStatus::Published)
            ->orderByRaw('starts_at IS NULL')
            ->orderBy('starts_at')
            ->get();

        return view('pages.public.site.events.index', [
            'bookingOpenEvents' => $events->filter(
                fn (Event $event): bool => $event->enrollment_status === EventEnrollmentStatus::BookingOpen,
            )->values(),
            'interestOpenEvents' => $events->filter(
                fn (Event $event): bool => $event->enrollment_status === EventEnrollmentStatus::InterestOpen,
            )->values(),
            'comingSoonEvents' => $events->filter(
                fn (Event $event): bool => $event->enrollment_status === EventEnrollmentStatus::ComingSoon,
            )->values(),
            'title' => 'الفعاليات',
            'metaDescription' => 'فعاليات وتجارب منتجع بيرحاء السياحية والتعليمية.',
        ]);
    }

    public function event(Event $event): View
    {
        abort_unless($event->status === EventStatus::Published, 404);

        return $this->renderEventLandingPage->handle($event);
    }

    public function about(AboutPageSettings $settings): View
    {
        $heroImage = $this->resolveMediaFiles([$settings->hero_image_id])[0] ?? null;

        return view('pages.public.site.about', [
            'about' => $settings,
            'heroImage' => $heroImage,
            'galleryImages' => $this->resolveMediaFiles($settings->gallery_image_ids),
            'title' => $settings->meta_title,
            'metaDescription' => $settings->meta_description,
            'metaImage' => $heroImage['url'] ?? null,
        ]);
    }

    public function contact(ContactPageSettings $settings): View
    {
        return view('pages.public.site.contact', [
            'contact' => $settings,
            'assistantUrl' => $this->safeExternalUrl($settings->assistant_url)
                ?? 'https://wa.me/96874155123?text='.rawurlencode('أرغب بالتحدث مع المساعد الذكي'),
            'mapUrl' => $this->safeExternalUrl($settings->map_url),
            'phoneHref' => preg_replace('/[^\d+]/', '', $settings->phone),
            'socialLinks' => $this->resolveSocialLinks($settings->social_links),
            'title' => $settings->meta_title,
            'metaDescription' => $settings->meta_description,
        ]);
    }

    /**
     * @param  array<int, mixed>  $fileIds
     * @return array<int, array{url: string, alt: string}>
     */
    private function resolveMediaFiles(array $fileIds): array
    {
        $ids = collect($fileIds)
            ->filter(fn (mixed $id): bool => is_numeric($id))
            ->map(fn (mixed $id): string => (string) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $files = File::query()
            ->with('media')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy(fn (File $file): string => (string) $file->getKey());

        return $ids
            ->map(function (string $id) use ($files): ?array {
                /** @var File|null $file */
                $file = $files->get($id);
                $url = $file?->getUrl();

                if (blank($url)) {
                    return null;
                }

                return [
                    'url' => $url,
                    'alt' => $file->name ?: 'صورة من مخيم بيرحاء إبراء',
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $links
     * @return array<int, array{label: string, url: string}>
     */
    private function resolveSocialLinks(array $links): array
    {
        $labels = [
            'instagram' => 'إنستغرام',
            'x' => 'إكس',
            'facebook' => 'فيسبوك',
            'youtube' => 'يوتيوب',
            'tiktok' => 'تيك توك',
            'snapchat' => 'سناب شات',
            'linkedin' => 'لينكدإن',
        ];

        return collect($links)
            ->filter(fn (mixed $link): bool => is_array($link))
            ->map(function (array $link) use ($labels): ?array {
                $platform = $link['platform'] ?? null;
                $url = $this->safeExternalUrl($link['url'] ?? null);

                if (! is_string($platform) || ! isset($labels[$platform]) || $url === null) {
                    return null;
                }

                return ['label' => $labels[$platform], 'url' => $url];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function safeExternalUrl(?string $url): ?string
    {
        if (blank($url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true) ? $url : null;
    }
}
