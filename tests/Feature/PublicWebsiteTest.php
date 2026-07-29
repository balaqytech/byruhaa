<?php

use App\Enums\BlogPostStatus;
use App\Enums\EventStatus;
use App\Models\BlogPost;
use App\Models\BlogPostCategory;
use App\Models\Discount;
use App\Models\Event;
use App\Models\EventPaymentPlan;
use App\Models\EventPaymentPlanInstallment;
use App\Support\EventLandingPageRegistry;
use Illuminate\Support\Facades\Route;

test('homepage loads', function () {
    $umrah = Event::query()->where('slug', 'umrah-2026')->firstOrFail();

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('منتجع بيرحاء')
        ->assertSee('مساحةٌ ينضج فيها الفتى بالفعل')
        ->assertSee('يومٌ متوازن، وأثرٌ يمتد')
        ->assertSee('التربية تبدأ بالصحبة، لا بالشعار')
        ->assertSee($umrah->name)
        ->assertSee(route('events.show', $umrah), false)
        ->assertSee(route('customer.events.show', $umrah), false)
        ->assertSee(route('events.index'), false)
        ->assertSee(route('coffee'), false)
        ->assertSee('قهوة بيرحاء، بابٌ يومي للمكان')
        ->assertSee(route('register'), false)
        ->assertSee('حسابي')
        ->assertSee('logo-dark.png', false)
        ->assertSee(route('affiliate.login'), false)
        ->assertSee(route('affiliate.register'), false)
        ->assertSee('data-icon="home-01"', false)
        ->assertSee('data-icon="calendar-03"', false)
        ->assertDontSee('cdn.hugeicons.com', false)
        ->assertDontSee('hgi-stroke', false);
});

test('coffee page shows the launch menu without a parallel store', function () {
    expect(config('coffee.groups'))->toHaveCount(4)
        ->and(config('coffee.currency'))->toBe('OMR');

    $this->assertFileExists(public_path('images/coffee-byruha-hero.webp'));
    $this->assertFileExists(public_path('images/coffee-byruha-menu.webp'));

    $this->get(route('coffee'))
        ->assertSuccessful()
        ->assertViewIs('pages.public.site.coffee')
        ->assertSee('قهوة بيرحاء')
        ->assertSee('القائمة الافتتاحية')
        ->assertSee('V60 حبوب الموسم')
        ->assertSee('كرواسون اللوز')
        ->assertSee('2.200')
        ->assertSee('البيع والاستلام من الموقع')
        ->assertSee('images/coffee-byruha-hero.webp', false)
        ->assertSee('images/coffee-byruha-menu.webp', false)
        ->assertSee('https://wa.me/96874155123', false)
        ->assertDontSee('أضف إلى السلة')
        ->assertDontSee('الدفع الآن');
});

test('umrah event is seeded with its canonical landing page data', function () {
    $event = Event::query()->where('slug', 'umrah-2026')->firstOrFail();

    expect($event->name)->toBe('رحلة العمرة بصحبة أبي بلج')
        ->and($event->status)->toBe(EventStatus::Published)
        ->and($event->landing_page_key)->toBe('umrah-2026-v1')
        ->and($event->minimum_age)->toBe(16)
        ->and($event->maximum_age)->toBe(18)
        ->and($event->seat_capacity)->toBe(30)
        ->and($event->price_baisa)->toBe(460000)
        ->and($event->starts_at?->toDateString())->toBe('2026-08-20')
        ->and($event->ends_at?->toDateString())->toBe('2026-08-29');

    $this->assertFileExists(public_path('images/umrah-2026-hero.png'));

    $this->get(route('events.show', $event))
        ->assertSuccessful()
        ->assertSee('images/umrah-2026-hero.png', false)
        ->assertSee('رحلة العمرة بصحبة أبي بلج')
        ->assertSee('٢٠ إلى ٢٩ أغسطس ٢٠٢٦')
        ->assertSee('سعر واحد داخل النظام')
        ->assertSee(route('events.show', $event), false)
        ->assertSee(route('customer.events.show', $event), false)
        ->assertDontSee('BYRUHAA EVENT');
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
        ->assertSee('images/after-twelfth-omani-graduate-hero.png', false)
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
        ->assertSee('Nearest published event')
        ->assertSee(route('events.show', $nearestEvent), false)
        ->assertSee(route('customer.events.show', $nearestEvent), false)
        ->assertDontSee('Hidden draft event');
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
