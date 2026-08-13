<?php

use App\Enums\BlogPostStatus;
use App\Enums\EventEnrollmentStatus;
use App\Enums\EventStatus;
use App\Enums\SeatAllocationState;
use App\Modules\Content\Models\BlogPost;
use App\Modules\Content\Models\BlogPostCategory;
use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\BookingSeatAllocation;
use App\Modules\Events\Models\Discount;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventPaymentPlan;
use App\Modules\Events\Models\EventPaymentPlanInstallment;
use App\Support\EventLandingPageRegistry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 1, 9));
});

afterEach(function () {
    Carbon::setTestNow();
});

test('homepage loads', function () {
    $featuredEvent = Event::query()->where('slug', 'after-twelfth-2026')->firstOrFail();
    $featuredEvent->update(['status' => EventStatus::Published]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('منتجع بيرحاء')
        ->assertSee('مساحةٌ ينضج فيها الفتى بالفعل')
        ->assertSee('يومٌ متوازن، وأثرٌ يمتد')
        ->assertSee('التربية تبدأ بالصحبة، لا بالشعار')
        ->assertSee($featuredEvent->name)
        ->assertSee(route('events.show', $featuredEvent), false)
        ->assertDontSee(route('customer.events.show', $featuredEvent), false)
        ->assertSee(route('events.index'), false)
        ->assertSee(route('coffee'), false)
        ->assertSee('قهوة بيرحاء، بابٌ يومي للمكان')
        ->assertSee(route('register'), false)
        ->assertSee('حسابي')
        ->assertSee('logo-dark.png', false)
        ->assertSee('data-icon="home-01"', false)
        ->assertSee('data-icon="calendar-03"', false)
        ->assertDontSee('cdn.hugeicons.com', false)
        ->assertDontSee('hgi-stroke', false)
        ->assertSee('data-whatsapp-floating-button', false)
        ->assertSee('pointer-events-none invisible', false)
        ->assertSee('data-whatsapp-reveal-sentinel', false)
        ->assertSee('data-public-footer', false)
        ->assertSee('data-home-events-link', false)
        ->assertSee('player.vimeo.com/video/1215490802', false)
        ->assertSee('autoplay=1&amp;muted=1', false)
        ->assertSee('images/after-twelfth-omani-graduate-hero.png', false)
        ->assertSee('x-on:load', false)
        ->assertSee('جميع الحقوق محفوظة.')
        ->assertSee('تطوير')
        ->assertSee('ردء')
        ->assertSee('https://red1ai.com/', false);
});

test('public event cards show their editorial card content', function () {
    $event = Event::factory()->create([
        'name' => 'ماذا أقول؟ وماذا أؤثر؟',
        'subtitle' => 'مهارات الكلام والتعامل مع الناس',
        'card_topics' => ['العبادة', 'قول الحسن'],
        'excerpt' => 'يتعلم الفتى كيف يتكلم ويصغي ويختار كلماته في المواقف اليومية.',
        'schedule_text' => 'من الخميس عصرًا إلى السبت عصرًا · ٤٨ ساعة',
    ]);

    $this->get(route('events.index'))
        ->assertSuccessful()
        ->assertSee('ماذا أقول؟ وماذا أؤثر؟')
        ->assertSee('مهارات الكلام والتعامل مع الناس')
        ->assertSee('العبادة')
        ->assertSee('قول الحسن')
        ->assertSee('من الخميس عصرًا إلى السبت عصرًا · ٤٨ ساعة')
        ->assertSee('data-event-card', false)
        ->assertSee('has-[details[open]]:z-50', false)
        ->assertSee(route('events.show', $event), false)
        ->assertSee('احجز الآن')
        ->assertDontSee($event->starts_at->format('Y-m-d'));

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('مهارات الكلام والتعامل مع الناس')
        ->assertSee('من الخميس عصرًا إلى السبت عصرًا · ٤٨ ساعة');
});

test('coffee page renders the database-backed public storefront', function () {
    expect(config('coffee.groups'))->toHaveCount(4)
        ->and(config('coffee.currency'))->toBe('OMR');

    $this->assertFileExists(public_path('images/coffee-byruha-hero.webp'));
    $this->assertFileExists(public_path('images/coffee-byruha-menu.webp'));

    $this->get(route('coffee'))
        ->assertSuccessful()
        ->assertViewIs('pages.public.site.coffee')
        ->assertSee('قهوة بيرحاء')
        ->assertSee('اطلب مسبقًا')
        ->assertSee('wire:id=', false)
        ->assertSee('images/coffee-byruha-hero.webp', false)
        ->assertSee('images/coffee-byruha-menu.webp', false)
        ->assertSee('https://wa.me/96874155123', false)
        ->assertSee('relative z-10 mt-4 max-w-md', false)
        ->assertDontSee('-mt-6', false);
});

test('umrah event is seeded with its canonical landing page data', function () {
    $event = Event::query()->where('slug', 'umrah-2026')->firstOrFail();
    $priceTiers = $event->priceTiers()->get();

    expect($event->name)->toBe('رحلة العمرة بصحبة أبي بلج')
        ->and($event->status)->toBe(EventStatus::Published)
        ->and($event->landing_page_key)->toBe('umrah-2026-v1')
        ->and($event->minimum_age)->toBe(16)
        ->and($event->maximum_age)->toBe(18)
        ->and($event->seat_capacity)->toBe(30)
        ->and($event->price_baisa)->toBe(460000)
        ->and($event->starts_at?->toDateString())->toBe('2026-08-20')
        ->and($event->ends_at?->toDateString())->toBe('2026-08-29')
        ->and($priceTiers->pluck('name')->all())->toBe(['الباقة الأولى', 'المتقدمة', 'الختامية'])
        ->and($priceTiers->pluck('seat_capacity')->all())->toBe([8, 12, 10])
        ->and($priceTiers->pluck('price_baisa')->all())->toBe([380000, 420000, 460000])
        ->and($priceTiers->sum('seat_capacity'))->toBe(30);

    $this->assertFileExists(public_path('images/umrah-2026-hero.png'));
    $this->assertFileExists(public_path('images/umrah-2026-preparation-v2.webp'));

    $this->get(route('events.show', $event))
        ->assertSuccessful()
        ->assertViewIs('pages.public.site.events.landings.umrah-2026')
        ->assertSee('images/umrah-2026-hero.png', false)
        ->assertSee('images/umrah-2026-preparation-v2.webp', false)
        ->assertSee('رحلة العمرة بصحبة أبي بلج')
        ->assertSee('٢٠ إلى ٢٩ أغسطس ٢٠٢٦')
        ->assertSee('لماذا هذه الرحلة الآن؟')
        ->assertSee('نفهم ما يدور في خاطرك قبل أن تسأل')
        ->assertSee('خماسية السكينة في أيام المخيم')
        ->assertSee('ما الذي يبقى بعد أن تُطوى الحقائب؟')
        ->assertSee('ما يحمله القائد معه')
        ->assertSee('طواف الوداع بعد صلاة العصر')
        ->assertSee('خصوصية القُصّر')
        ->assertSee('الباقة الأولى')
        ->assertSee('380.000')
        ->assertSee('المتقدمة')
        ->assertSee('420.000')
        ->assertSee('الختامية')
        ->assertSee('460.000')
        ->assertSee(route('events.show', $event), false)
        ->assertSee(route('customer.events.show', $event), false)
        ->assertDontSee('سعر واحد داخل النظام')
        ->assertDontSee('BYRUHAA EVENT');
});

test('umrah landing page derives the open price tier from held and reserved seats', function () {
    $event = Event::query()->where('slug', 'umrah-2026')->firstOrFail();
    $firstTier = $event->priceTiers()->firstOrFail();
    $booking = Booking::factory()->for($event)->create();

    BookingSeatAllocation::factory()->for($booking)->create([
        'event_id' => $event->id,
        'event_price_tier_id' => $firstTier->id,
        'seat_count' => 8,
        'state' => SeatAllocationState::Held,
        'tier_name' => $firstTier->name,
        'tier_unit_price_baisa' => $firstTier->price_baisa,
    ]);

    $this->get(route('events.show', $event))
        ->assertSuccessful()
        ->assertSeeInOrder(['الباقة الأولى', 'نفدت', 'المتقدمة', 'مفتوحة الآن'])
        ->assertSee('٢٢ من ٣٠ مقعدًا');
});

test('after twelfth event is seeded with its landing page and price tiers', function () {
    $event = Event::query()->where('slug', 'after-twelfth-2026')->firstOrFail();
    $priceTiers = $event->priceTiers()->get();

    expect($event->name)->toBe('بعد الثاني عشر، الطريق يبدأ')
        ->and($event->status)->toBe(EventStatus::Published)
        ->and($event->landing_page_key)->toBe('life-after-school-v1')
        ->and($event->minimum_age)->toBe(17)
        ->and($event->maximum_age)->toBe(18)
        ->and($event->seat_capacity)->toBe(50)
        ->and($event->price_baisa)->toBe(89000)
        ->and($event->starts_at?->toDateTimeString())->toBe('2026-08-13 12:00:00')
        ->and($event->ends_at)->toBeNull()
        ->and($priceTiers->pluck('name')->all())->toBe([
            'الباقة الأولى',
            'الباقة الثانية',
            'الباقة الثالثة',
            'الباقة الرابعة',
            'الباقة الخامسة',
        ])
        ->and($priceTiers->pluck('seat_capacity')->all())->toBe([10, 10, 10, 10, 10])
        ->and($priceTiers->pluck('price_baisa')->all())->toBe([59000, 69000, 75000, 79000, 89000])
        ->and($priceTiers->sum('seat_capacity'))->toBe(50);

    $this->assertFileExists(public_path('images/after-twelfth-omani-graduate-hero.png'));
    $this->assertFileExists(public_path('images/after-twelfth-mentor-circle.png'));

    $this->get(route('events.show', $event))
        ->assertSuccessful()
        ->assertViewIs('pages.public.site.events.landings.life-after-school-v1')
        ->assertSee('player.vimeo.com/video/1215490802', false)
        ->assertSee('images/after-twelfth-omani-graduate-hero.png', false)
        ->assertSee('x-on:load', false)
        ->assertSee('بعد الثاني عشر')
        ->assertSee('تبدأ ١٣ أغسطس ٢٠٢٦م')
        ->assertSee('خطة ٩٠ يومًا')
        ->assertSee('ست محطات في ثلاثة أيام')
        ->assertSee('صحبة تبقى')
        ->assertSeeInOrder([
            'ما تشمله رسوم المقعد',
            'المقعد التدريبي كاملًا، ست محطات',
            'الإقامة داخل مخيم بيرحاء طوال الأيام الثلاثة',
            'زيارة حديقة الحيوان «عالم سفاري»',
            'تقرير ختامي فردي عن ابنك',
            'قاعدة الوضوح',
            'التسعير المتدرج',
        ])
        ->assertSee('الخمسة عشر بندًا أعلاه مشمولة بالكامل')
        ->assertDontSee('قيمتان مضافتان')
        ->assertDontSee('ما لا تشمله الرسوم')
        ->assertSee('الباقة الأولى')
        ->assertSee('59.000')
        ->assertSee('الباقة الثانية')
        ->assertSee('69.000')
        ->assertDontSee('الباقة الثالثة')
        ->assertSee('احجز الآن')
        ->assertDontSee('مقاعد الرحمة')
        ->assertDontSee('استفسر قبل الحجز')
        ->assertSee(route('events.show', $event), false)
        ->assertSee(route('customer.events.show', $event), false)
        ->assertDontSee('٦-٨ أغسطس')
        ->assertDontSee('خصم الإخوة')
        ->assertDontSee('شهادات حقيقية، قريبًا');
});

test('after twelfth landing page advances to the next price tier when the first is full', function () {
    $event = Event::query()->where('slug', 'after-twelfth-2026')->firstOrFail();
    $firstTier = $event->priceTiers()->firstOrFail();
    $booking = Booking::factory()->for($event)->create();

    BookingSeatAllocation::factory()->for($booking)->create([
        'event_id' => $event->id,
        'event_price_tier_id' => $firstTier->id,
        'seat_count' => 10,
        'state' => SeatAllocationState::Held,
        'tier_name' => $firstTier->name,
        'tier_unit_price_baisa' => $firstTier->price_baisa,
    ]);

    $this->get(route('events.show', $event))
        ->assertSuccessful()
        ->assertSeeInOrder([
            'الباقة الثانية',
            '69.000',
            'الباقة الثالثة',
            '75.000',
        ])
        ->assertDontSee('الباقة الرابعة');
});

test('the temporary new home route is removed', function () {
    expect(Route::has('new-home'))->toBeFalse();

    $this->get('/new-home')->assertNotFound();
});

test('published events can use a registered landing page at their canonical URL', function () {
    $event = Event::factory()->create([
        'landing_page_key' => 'life-after-school-v1',
        'seat_capacity' => 40,
    ]);

    $this->get(route('events.show', $event))
        ->assertSuccessful()
        ->assertSee('player.vimeo.com/video/1215490802', false)
        ->assertSee(route('events.show', $event), false)
        ->assertSee(route('customer.events.show', $event), false)
        ->assertDontSee('BYRUHAA EVENT');
});

test('unknown landing page keys safely fall back to the standard event page', function () {
    $event = Event::factory()->create([
        'name' => 'Fallback landing event',
        'landing_page_key' => 'not-registered',
    ]);

    $this->get(route('events.show', $event))
        ->assertSuccessful()
        ->assertSee('Fallback landing event')
        ->assertSee('BYRUHAA EVENT');
});

test('landing page registry only exposes registered views that exist', function () {
    config()->set('event-landings.pages.missing-view', [
        'label' => 'Missing view',
        'view' => 'event-landings.missing.show',
    ]);

    $landingPages = app(EventLandingPageRegistry::class);

    expect($landingPages->options())
        ->toHaveKey('life-after-school-v1')
        ->toHaveKey('umrah-2026-v1')
        ->not->toHaveKey('missing-view')
        ->and($landingPages->resolve('missing-view'))
        ->toBe('pages.public.site.events.show');
});

test('homepage features the nearest published event and hides drafts', function () {
    $nearestEvent = Event::factory()->create([
        'name' => 'Nearest published event',
        'status' => EventStatus::Published,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDays(2),
    ]);
    Event::factory()->draft()->create(['name' => 'Hidden draft event']);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSeeInOrder(['Nearest published event', 'مساحةٌ ينضج فيها الفتى بالفعل'])
        ->assertSee(route('events.show', $nearestEvent), false)
        ->assertDontSee(route('customer.events.show', $nearestEvent), false)
        ->assertDontSee('Hidden draft event');
});

test('homepage shows the current price tier followed by the next tier', function () {
    $event = Event::query()->where('slug', 'after-twelfth-2026')->firstOrFail();
    $event->update(['status' => EventStatus::Published]);
    $firstTier = $event->priceTiers()->firstOrFail();
    $booking = Booking::factory()->for($event)->create();

    BookingSeatAllocation::factory()->for($booking)->create([
        'event_id' => $event->id,
        'event_price_tier_id' => $firstTier->id,
        'seat_count' => 3,
        'state' => SeatAllocationState::Reserved,
        'tier_name' => $firstTier->name,
        'tier_unit_price_baisa' => $firstTier->price_baisa,
    ]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSeeInOrder([
            'الباقة المتاحة الآن',
            'الباقة الأولى',
            'بقي 7 مقاعد',
            'الباقة التالية',
            'الباقة الثانية',
            '69.000',
        ]);
});

test('events page loads', function () {
    $this->get(route('events.index'))
        ->assertSuccessful()
        ->assertSee('الفعاليات');
});

test('events page groups published events by enrollment status', function () {
    $bookingOpenEvent = Event::factory()->create([
        'name' => 'Booking open event',
        'enrollment_status' => EventEnrollmentStatus::BookingOpen,
        'starts_at' => now()->addDays(1),
    ]);
    $interestOpenEvent = Event::factory()->create([
        'name' => 'Interest open event',
        'enrollment_status' => EventEnrollmentStatus::InterestOpen,
        'starts_at' => now()->addDays(2),
    ]);
    $comingSoonEvent = Event::factory()->create([
        'name' => 'Coming soon event',
        'enrollment_status' => EventEnrollmentStatus::ComingSoon,
        'starts_at' => now()->addDays(3),
    ]);
    $closedEvent = Event::factory()->create([
        'name' => 'Closed event',
        'enrollment_status' => EventEnrollmentStatus::BookingClosed,
        'starts_at' => now()->addDays(4),
    ]);

    $this->get(route('events.index'))
        ->assertSuccessful()
        ->assertSeeInOrder([
            'فعاليات مفتوحة للحجز',
            $bookingOpenEvent->name,
            'فعاليات نستقبل المهتمين بها',
            $interestOpenEvent->name,
            'فعاليات قادمة',
            $comingSoonEvent->name,
        ])
        ->assertDontSee($closedEvent->name);
});

test('blog page loads', function () {
    $this->get(route('blog.index'))
        ->assertSuccessful()
        ->assertSee(__('ui.blog.heading'));
});

test('about page loads', function () {
    $this->get(route('about'))
        ->assertSuccessful()
        ->assertSee('مخيم بيرحاء إبراء')
        ->assertSee('+10,000')
        ->assertSee('قاعة متعددة الاستخدام')
        ->assertSee('مطعم أباريق')
        ->assertSee('إحدى عشرة غرفة إقامة')
        ->assertSee('ما الذي يميّز بيرحاء؟')
        ->assertDontSee('قيد التجهيز');
});

test('contact page loads', function () {
    $this->get(route('contact'))
        ->assertSuccessful()
        ->assertSee('نحن قريبون منك')
        ->assertSee('ابدأ مع مساعد بيرحاء الذكي')
        ->assertSee('+968 7415 5123')
        ->assertSee('الزيارة بموعد مسبق')
        ->assertSee('noopener noreferrer', false)
        ->assertDontSee('قيد التجهيز');
});

test('public error pages use the public website style', function () {
    foreach ([403, 404, 419, 429, 500, 503] as $status) {
        $this->view("errors.{$status}")
            ->assertSee((string) $status)
            ->assertSee(__('ui.errors.label'))
            ->assertSee(__('ui.errors.home'))
            ->assertSee('logo-dark.png', false)
            ->assertSee('data-icon=', false);
    }

    $this->get('/missing-public-page')
        ->assertNotFound()
        ->assertSee(__('ui.errors.404.title'))
        ->assertSee(route('home'), false)
        ->assertDontSee('hgi-stroke', false);
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

test('public event detail page shows event description discounts and payment plans', function () {
    $event = Event::factory()->create([
        'name' => 'Elegant mountain camp',
        'status' => EventStatus::Published,
        'excerpt' => 'A calm public event overview.',
        'description_html' => '<p>Guided hikes, workshops, and quiet evenings.</p>',
        'price_baisa' => 12000,
    ]);
    $draftEvent = Event::factory()->create([
        'status' => EventStatus::Draft,
        'landing_page_key' => 'life-after-school-v1',
    ]);
    $discount = Discount::factory()->for($event)->create([
        'name' => 'Sibling saving',
        'amount_baisa' => 2000,
        'minimum_family_members' => 2,
    ]);
    $paymentPlan = EventPaymentPlan::factory()->for($event)->create(['name' => 'Two payments']);

    EventPaymentPlanInstallment::factory()->for($paymentPlan, 'paymentPlan')->create([
        'name' => 'Deposit',
        'sequence' => 1,
        'percentage' => 50,
        'due_date' => '2026-07-01',
    ]);
    EventPaymentPlanInstallment::factory()->for($paymentPlan, 'paymentPlan')->create([
        'name' => 'Final',
        'sequence' => 2,
        'percentage' => 50,
        'due_date' => '2026-08-01',
    ]);

    $this->get(route('events.show', $event))
        ->assertSuccessful()
        ->assertSee('Elegant mountain camp')
        ->assertSee('Guided hikes, workshops, and quiet evenings.')
        ->assertSee($discount->name)
        ->assertSee('2.000')
        ->assertSee(__('ui.payments.full_payment'))
        ->assertSee(__('ui.payments.full_payment_description'))
        ->assertSeeInOrder([__('ui.payments.full_payment'), 'Two payments'])
        ->assertSee('Two payments')
        ->assertSee('12.000')
        ->assertSee('Deposit')
        ->assertSee('Final')
        ->assertSee('6.000')
        ->assertSee(route('customer.events.show', $event), false);

    $comingSoonEvent = Event::factory()->create([
        'name' => 'Coming Soon Camp',
        'status' => EventStatus::Published,
        'enrollment_status' => EventEnrollmentStatus::ComingSoon,
        'excerpt' => 'Details will be published soon.',
        'location' => 'إبراء، مخيم بيرحاء',
        'starts_at' => now()->addDays(30),
        'price_baisa' => 25000,
    ]);

    $this->get(route('events.show', $comingSoonEvent))
        ->assertSuccessful()
        ->assertSee('Coming Soon Camp')
        ->assertSee('Details will be published soon.')
        ->assertSee('إبراء، مخيم بيرحاء')
        ->assertSee($comingSoonEvent->starts_at->format('Y-m-d'))
        ->assertDontSee('السعر لكل فرد')
        ->assertDontSee('المقاعد المتبقية')
        ->assertDontSee('السعة')
        ->assertDontSee(__('ui.payments.full_payment'))
        ->assertDontSee('Two payments')
        ->assertDontSee('BYRUHAA EVENT')
        ->assertDontSee(route('customer.events.show', $comingSoonEvent), false);

    $this->get(route('events.show', $draftEvent))
        ->assertNotFound();
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
