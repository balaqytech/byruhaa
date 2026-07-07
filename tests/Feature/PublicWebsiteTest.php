<?php

use App\Enums\BlogPostStatus;
use App\Enums\EventStatus;
use App\Models\BlogPost;
use App\Models\BlogPostCategory;
use App\Models\Discount;
use App\Models\Event;
use App\Models\EventPaymentPlan;
use App\Models\EventPaymentPlanInstallment;

test('homepage loads', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('منتجع بيرحاء')
        ->assertSee('قريبًا فعاليات أكثر في بيرحاء')
        ->assertSee('رزنامة بيرحاء قيد التجهيز')
        ->assertSee('تصفح الفعاليات الحالية')
        ->assertSee('رحلات في الطبيعة')
        ->assertSee('ورش وتجارب تعليمية')
        ->assertSee('مخيمات عائلية')
        ->assertSee(route('events.index'), false)
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

test('new home hero uses the Omani graduate reel image for mobile', function () {
    $this->assertFileExists(public_path('images/after-twelfth-omani-graduate-hero.png'));

    $this->get(route('new-home'))
        ->assertSuccessful()
        ->assertSee('images/after-twelfth-omani-graduate-hero.png', false)
        ->assertSee('aspect-[9/16]', false)
        ->assertSee('width="941" height="1672"', false)
        ->assertSee('order-1 overflow-hidden', false)
        ->assertSee('order-2 max-w-4xl', false)
        ->assertSee('lg:order-2', false)
        ->assertSee('lg:order-1', false);
});

test('homepage keeps the coming soon page when the configured event exists', function () {
    Event::factory()->create([
        'id' => 1,
        'name' => 'Older public event',
        'slug' => 'older-public-event',
        'status' => EventStatus::Published,
    ]);

    $event = Event::factory()->create([
        'id' => 2,
        'name' => 'Official life after school event',
        'slug' => 'your-guide-to-life-after-school',
        'status' => EventStatus::Published,
        'seat_capacity' => 40,
    ]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('قريبًا فعاليات أكثر في بيرحاء')
        ->assertSee(route('events.index'), false)
        ->assertDontSee(route('events.show', $event), false)
        ->assertDontSee(route('customer.events.show', $event), false)
        ->assertDontSee(route('events.show', 'older-public-event'), false);
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
    $draftEvent = Event::factory()->create(['status' => EventStatus::Draft]);
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
