<?php

use App\Enums\EventEnrollmentStatus;
use App\Enums\EventStatus;
use App\Modules\Events\Models\Event;
use Illuminate\Support\Facades\DB;

test('rabbaniyeen is published with its custom landing page and an interest form', function () {
    $event = Event::query()->where('slug', 'rabbaniyeen')->firstOrFail();

    expect($event->name)->toBe('ربانيين')
        ->and($event->status)->toBe(EventStatus::Published)
        ->and($event->enrollment_status)->toBe(EventEnrollmentStatus::InterestOpen)
        ->and($event->landing_page_key)->toBe('rabbaniyeen-v1')
        ->and($event->price_baisa)->toBe(0);

    $this->get(route('events.show', $event))
        ->assertSuccessful()
        ->assertViewIs('pages.public.site.events.landings.rabbaniyeen-v1')
        ->assertSee('سطران في اليوم، ومشرفٌ يعرف ابنك باسمه.')
        ->assertSee('ثلاثةُ مسارات')
        ->assertSee('من يدير البرنامج')
        ->assertSee('أبو بلج عبدالله العيسري')
        ->assertSee('أبو الخليل إبراهيم الغافري')
        ->assertSee('images/rabbaniyeen-hero.png')
        ->assertSee('اسم الطالب/ القائد')
        ->assertSee('سجِّل اهتمامي')
        ->assertSee(route('events.rabbaniyeen.interests.store', $event), false)
        ->assertSee(route('policies.show', 'privacy'), false)
        ->assertDontSee('كيف يمشي يومُ الفتى؟')
        ->assertDontSee('أربعةُ أشياءَ نفعلها')
        ->assertDontSee('أسئلةٌ يسألها كلُّ أب')
        ->assertDontSee('هل يقرأ المصحفَ المشكولَ بطلاقة؟')
        ->assertDontSee('المسارُ الذي تراه مناسبًا مبدئيًّا')
        ->assertDontSee('لديّ أكثر من ابنٍ في صفوفٍ مختلفة')
        ->assertDontSee('96890000000');

    $this->get(route('events.index'))
        ->assertSuccessful()
        ->assertSee(route('events.show', $event).'#sajjil', false)
        ->assertSee('سجّل اهتمامك');
});

test('a guest can submit and update rabbaniyeen interest without duplicate leads', function () {
    $event = Event::query()->where('slug', 'rabbaniyeen')->firstOrFail();
    $url = route('events.rabbaniyeen.interests.store', $event);
    $data = [
        'guardian_name' => 'محمد القباطي',
        'student_name' => 'علي القباطي',
        'whatsapp_number' => '٩٦٨ ٩١٢٣ ٤٥٦٧',
        'wilaya' => 'إبراء',
        'student_grade' => '7',
        'recitation_level' => 'hesitant',
        'preferred_track' => 'sakinah',
        'note' => 'هل يوجد نقل؟',
        'contact_consent' => '1',
    ];

    $this->post($url, $data)
        ->assertRedirect(route('events.show', $event).'#sajjil')
        ->assertSessionHas('rabbaniyeen_interest_reference');

    $reference = DB::table('event_interest_leads')->where('event_id', $event->id)->value('reference_code');

    expect($reference)->toStartWith('RB-');

    $this->assertDatabaseHas('event_interest_leads', [
        'event_id' => $event->id,
        'guardian_name' => 'محمد القباطي',
        'student_name' => 'علي القباطي',
        'whatsapp_number' => '96891234567',
        'student_grade' => '7',
        'recitation_level' => null,
        'preferred_track' => null,
    ]);

    $this->get(route('events.show', $event))->assertSee($reference);

    $this->post($url, [
        ...$data,
        'whatsapp_number' => '+968-9123-4567',
        'note' => 'أرجو التواصل مساءً',
    ])->assertRedirect(route('events.show', $event).'#sajjil');

    expect(DB::table('event_interest_leads')->where('event_id', $event->id)->count())->toBe(1)
        ->and(DB::table('event_interest_leads')->where('event_id', $event->id)->value('reference_code'))->toBe($reference);

    $this->assertDatabaseHas('event_interest_leads', ['note' => 'أرجو التواصل مساءً']);

    $this->post($url, [
        ...$data,
        'student_name' => 'خالد القباطي',
        'student_grade' => '8',
    ])->assertRedirect(route('events.show', $event).'#sajjil');

    expect(DB::table('event_interest_leads')->where('event_id', $event->id)->count())->toBe(2);

    $this->assertDatabaseHas('event_interest_leads', [
        'event_id' => $event->id,
        'whatsapp_number' => '96891234567',
        'student_name' => 'خالد القباطي',
        'student_grade' => '8',
    ]);
});

test('rabbaniyeen interest requires valid details and consent', function () {
    $event = Event::query()->where('slug', 'rabbaniyeen')->firstOrFail();

    $this->from(route('events.show', $event))
        ->post(route('events.rabbaniyeen.interests.store', $event), [
            'guardian_name' => '',
            'student_name' => '',
            'whatsapp_number' => '123',
            'wilaya' => '',
            'student_grade' => '6',
        ])
        ->assertRedirect(route('events.show', $event))
        ->assertSessionHasErrors([
            'guardian_name', 'student_name', 'whatsapp_number', 'wilaya',
            'student_grade', 'contact_consent',
        ]);

    $this->post(route('events.rabbaniyeen.interests.store', $event), [
        'guardian_name' => 'ولي أمر',
        'student_name' => 'طالب',
        'whatsapp_number' => '96891234567',
        'wilaya' => 'إبراء',
        'student_grade' => 'multiple',
        'contact_consent' => '1',
    ])->assertSessionHasErrors('student_grade');

    expect(DB::table('event_interest_leads')->count())->toBe(0);
});

test('the rabbaniyeen lead endpoint rejects other events and closed interest', function () {
    $event = Event::query()->where('slug', 'rabbaniyeen')->firstOrFail();
    $otherEvent = Event::factory()->create();
    $data = [
        'guardian_name' => 'ولي أمر',
        'student_name' => 'طالب',
        'whatsapp_number' => '96891234567',
        'wilaya' => 'إبراء',
        'student_grade' => '8',
        'contact_consent' => '1',
    ];

    $this->post(route('events.rabbaniyeen.interests.store', $otherEvent), $data)->assertNotFound();

    $event->update(['enrollment_status' => EventEnrollmentStatus::BookingClosed]);

    $this->post(route('events.rabbaniyeen.interests.store', $event), $data)->assertNotFound();

    expect(DB::table('event_interest_leads')->count())->toBe(0);
});
