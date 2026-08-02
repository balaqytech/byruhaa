<?php

use App\Enums\EventEnrollmentStatus;
use App\Enums\EventStatus;
use App\Models\Event;
use Database\Seeders\ShortCoursesSeeder;

test('short courses seeder creates all interest events without dates and is idempotent', function () {
    $this->seed(ShortCoursesSeeder::class);
    $this->seed(ShortCoursesSeeder::class);

    $expectedSlugs = [
        'what-to-say-and-how-to-respond',
        'stand-up-and-speak',
        'how-to-master-a-book',
        'connection-with-heaven',
        'my-family-tree',
        'my-first-earned-rial',
        'from-player-to-maker',
        'son-of-the-wilderness',
        'man-of-the-house',
        'first-responder',
        'making-an-impact',
        'my-seven-star-room',
        'the-last-hour-of-your-day',
        'from-idea-to-text',
        'growing-my-wallet',
        'memorize-without-forgetting',
        'the-first-hour-shapes-your-day',
        'an-appointment-i-never-miss',
        'my-farm-with-my-own-hands',
        'laboratory-of-the-universe',
        'simulation-field',
        'on-stage',
        'making-a-champion',
        'master-of-the-table',
        'the-giving-hand',
    ];

    $courses = Event::query()
        ->whereIn('slug', $expectedSlugs)
        ->orderBy('slug')
        ->get();

    expect($courses)->toHaveCount(25)
        ->and($courses->pluck('slug')->unique())->toHaveCount(25)
        ->and($courses->pluck('slug')->sort()->values()->all())->toBe(collect($expectedSlugs)->sort()->values()->all())
        ->and(Event::query()->where('slug', 'like', 'brc-%')->exists())->toBeFalse()
        ->and($courses->every(fn (Event $event): bool => $event->starts_at === null && $event->ends_at === null))->toBeTrue()
        ->and($courses->every(fn (Event $event): bool => $event->status === EventStatus::Published))->toBeTrue()
        ->and($courses->every(fn (Event $event): bool => $event->enrollment_status === EventEnrollmentStatus::InterestOpen))->toBeTrue()
        ->and($courses->every(fn (Event $event): bool => $event->minimum_age === 13 && $event->maximum_age === 18))->toBeTrue()
        ->and($courses->every(fn (Event $event): bool => $event->seat_capacity === 0 && $event->price_baisa === 0))->toBeTrue()
        ->and($courses->every(fn (Event $event): bool => $event->location === 'مخيم بيرحاء، إبراء، سلطنة عُمان'))->toBeTrue()
        ->and($courses->every(fn (Event $event): bool => $event->schedule_text === 'من الخميس عصرًا إلى السبت عصرًا · ٤٨ ساعة'))->toBeTrue()
        ->and($courses->firstWhere('slug', 'what-to-say-and-how-to-respond')->name)->toBe('ماذا أقول؟ وبماذا أرُدّ؟')
        ->and($courses->firstWhere('slug', 'what-to-say-and-how-to-respond')->subtitle)->toBe('مهاراتُ الكلام والتعامل مع الناس')
        ->and($courses->firstWhere('slug', 'what-to-say-and-how-to-respond')->card_topics)->toBe(['العبادة · قول الحسن'])
        ->and($courses->firstWhere('slug', 'what-to-say-and-how-to-respond')->excerpt)->toStartWith('يَعرف ابنُك ما يقول في قلبه')
        ->and($courses->firstWhere('slug', 'what-to-say-and-how-to-respond')->excerpt)->toContain('ثمانٍ وأربعون ساعةً من المواقف الحيّة')
        ->and(mb_strlen($courses->firstWhere('slug', 'what-to-say-and-how-to-respond')->excerpt))->toBe(274)
        ->and($courses->firstWhere('slug', 'the-giving-hand')->name)->toBe('يدٌ عُليا')
        ->and($courses->firstWhere('slug', 'what-to-say-and-how-to-respond')->description_html)->toContain('ثمانٍ وأربعون ساعةً من المواقف الحيّة');
});

test('short courses seeder stops when old and new slugs belong to different events', function () {
    $this->seed(ShortCoursesSeeder::class);
    Event::factory()->create(['slug' => 'brc-01']);

    expect(fn () => $this->seed(ShortCoursesSeeder::class))
        ->toThrow(LogicException::class, 'both old and new slugs already exist');
});

test('short courses seeder upgrades legacy card content without replacing admin edits', function () {
    $this->seed(ShortCoursesSeeder::class);

    $course = Event::query()->where('slug', 'what-to-say-and-how-to-respond')->firstOrFail();
    $course->update([
        'slug' => 'brc-01',
        'subtitle' => null,
        'excerpt' => 'مهاراتُ الكلام والتعامل مع الناس',
        'card_topics' => null,
        'schedule_text' => null,
    ]);

    $this->seed(ShortCoursesSeeder::class);

    expect($course->refresh())
        ->slug->toBe('what-to-say-and-how-to-respond')
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
