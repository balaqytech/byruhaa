@extends('layouts.public')

@section('content')
    @php
        $eventSections = [
            [
                'title' => 'فعاليات مفتوحة للحجز',
                'description' => 'اختر الفعالية المناسبة، ثم احجز مقعدك قبل اكتمال المقاعد المتاحة.',
                'events' => $bookingOpenEvents,
            ],
            [
                'title' => 'فعاليات نستقبل المهتمين بها',
                'description' => 'سجّل اهتمامك، وسنخبرك عند فتح التسجيل أو اكتمال تفاصيل الفعالية.',
                'events' => $interestOpenEvents,
            ],
            [
                'title' => 'فعاليات قادمة',
                'description' => 'تجارب نجهّز لها الآن، وستعلن تفاصيلها في الوقت المناسب.',
                'events' => $comingSoonEvents,
            ],
        ];
    @endphp

    <section class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="public-hero-copy max-w-3xl">
            <p class="inline-flex items-center gap-2 text-sm font-semibold text-[#009060] dark:text-[#e0a800]">
                <x-hugeicon name="calendar-03" class="text-lg" />
                <span>الفعاليات</span>
            </p>
            <h1 class="mt-4 font-heading text-4xl font-bold text-[#123329] sm:text-5xl dark:text-[#f7f1df]">تجارب مختارة
                بعناية</h1>
            <p class="mt-5 text-lg leading-8 text-[#123329]/68 dark:text-[#f7f1df]/68">
                رحلات ومخيمات ومواسم تعليمية مصممة بإيقاع هادئ وتفاصيل تليق بضيوف بيرحاء.
            </p>
        </div>

        <div class="mt-14 space-y-16 lg:mt-18 lg:space-y-22">
            @php($eventNumber = 0)
            @foreach ($eventSections as $section)
                @continue($section['events']->isEmpty())

                <section aria-labelledby="event-section-{{ $loop->index }}">
                    <div class="max-w-2xl">
                        <h2 id="event-section-{{ $loop->index }}" class="font-heading text-3xl font-bold text-[#123329] lg:text-4xl dark:text-[#f7f1df]">
                            {{ $section['title'] }}
                        </h2>
                        <p class="mt-3 leading-8 text-[#315e52] dark:text-[#d2e7df]/72">{{ $section['description'] }}</p>
                    </div>

                    <div class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($section['events'] as $event)
                            @php($eventNumber++)
                            <x-public-event-card :event="$event" :number="$eventNumber" />
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        @if ($eventNumber === 0)
            <div
                class="public-card mt-12 border border-dashed border-[#009060]/24 bg-white/66 px-6 py-16 text-center dark:border-[#e0a800]/24 dark:bg-white/8">
                <x-hugeicon name="calendar-remove-01" class="mx-auto text-4xl text-[#e0a800]" />
                <h2 class="mt-5 font-heading text-3xl font-bold text-[#123329] dark:text-[#f7f1df]">لا توجد فعاليات منشورة حالياً</h2>
                <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-[#123329]/64 dark:text-[#f7f1df]/64">
                    نعمل على تجهيز برنامج يليق بتجربة بيرحاء. ستظهر الفعاليات هنا فور فتح باب الحجز.
                </p>
            </div>
        @endif
    </section>
@endsection
