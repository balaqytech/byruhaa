<?php

use App\Enums\EventEnrollmentStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use Database\Seeders\ShortCoursesSeeder;

test('short courses seeder creates all interest events without dates and is idempotent', function () {
    $this->seed(ShortCoursesSeeder::class);
    $this->seed(ShortCoursesSeeder::class);

    $courses = Event::query()
        ->whereIn('slug', collect(range(1, 25))->map(fn (int $number): string => sprintf('brc-%02d', $number)))
        ->orderBy('slug')
        ->get();

    expect($courses)->toHaveCount(25)
        ->and($courses->pluck('slug')->unique())->toHaveCount(25)
        ->and($courses->every(fn (Event $event): bool => $event->starts_at === null && $event->ends_at === null))->toBeTrue()
        ->and($courses->every(fn (Event $event): bool => $event->status === EventStatus::Published))->toBeTrue()
        ->and($courses->every(fn (Event $event): bool => $event->enrollment_status === EventEnrollmentStatus::InterestOpen))->toBeTrue()
        ->and($courses->every(fn (Event $event): bool => $event->minimum_age === 13 && $event->maximum_age === 18))->toBeTrue()
        ->and($courses->every(fn (Event $event): bool => $event->seat_capacity === 0 && $event->price_baisa === 0))->toBeTrue()
        ->and($courses->every(fn (Event $event): bool => $event->location === 'مخيم بيرحاء، إبراء، سلطنة عُمان'))->toBeTrue()
        ->and($courses->every(fn (Event $event): bool => $event->schedule_text === 'من الخميس عصرًا إلى السبت عصرًا · ٤٨ ساعة'))->toBeTrue()
        ->and($courses->first()->name)->toBe('ماذا أقول؟ وبماذا أرُدّ؟')
        ->and($courses->first()->subtitle)->toBe('مهاراتُ الكلام والتعامل مع الناس')
        ->and($courses->first()->card_topics)->toBe(['العبادة · قول الحسن'])
        ->and($courses->first()->excerpt)->toStartWith('يَعرف ابنُك ما يقول في قلبه')
        ->and($courses->first()->excerpt)->toContain('ثمانٍ وأربعون ساعةً من المواقف الحيّة')
        ->and(mb_strlen($courses->first()->excerpt))->toBe(274)
        ->and($courses->last()->name)->toBe('يدٌ عُليا')
        ->and($courses->first()->description_html)->toContain('ثمانٍ وأربعون ساعةً من المواقف الحيّة');
});

test('short courses seeder upgrades legacy card content without replacing admin edits', function () {
    $this->seed(ShortCoursesSeeder::class);

    $course = Event::query()->where('slug', 'brc-01')->firstOrFail();
    $course->update([
        'subtitle' => null,
        'excerpt' => 'مهاراتُ الكلام والتعامل مع الناس',
        'card_topics' => null,
        'schedule_text' => null,
    ]);

    $this->seed(ShortCoursesSeeder::class);

    expect($course->refresh())
        ->subtitle->toBe('مهاراتُ الكلام والتعامل مع الناس')
        ->excerpt->toStartWith('يَعرف ابنُك ما يقول في قلبه')
        ->card_topics->toBe(['العبادة · قول الحسن'])
        ->schedule_text->toBe('من الخميس عصرًا إلى السبت عصرًا · ٤٨ ساعة');

    $course->update([
        'name' => 'اسم معدل من الإدارة',
        'subtitle' => 'عنوان فرعي معدل',
        'excerpt' => 'مقتطف معدل',
        'card_topics' => ['تصنيف معدل'],
        'schedule_text' => 'موعد معدل',
    ]);

    $this->seed(ShortCoursesSeeder::class);

    expect($course->refresh())
        ->name->toBe('اسم معدل من الإدارة')
        ->subtitle->toBe('عنوان فرعي معدل')
        ->excerpt->toBe('مقتطف معدل')
        ->card_topics->toBe(['تصنيف معدل'])
        ->schedule_text->toBe('موعد معدل');
});
