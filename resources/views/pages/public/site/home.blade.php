@extends('layouts.public', [
    'title' => $title,
    'metaDescription' => $metaDescription,
])

@php
    $featuredImage = match ($featuredEvent?->landing_page_key) {
        'rabbaniyeen-v1' => asset('images/rabbaniyeen-hero.png'),
        'umrah-2026-v1' => asset('images/umrah-2026-hero.png'),
        'life-after-school-v1' => asset('images/after-twelfth-omani-graduate-hero.png'),
        default => asset('images/after-twelfth-plan-workshop.png'),
    };
    $isRabbaniyeen = $featuredEvent?->landing_page_key === 'rabbaniyeen-v1';
    $featuredActionLabel = $isRabbaniyeen
        ? 'سجِّل اهتمامك'
        : ($featuredEvent?->canBook() ? 'احجز الآن' : 'تعرّف إلى الفعالية');
    $featuredActionUrl = $featuredEvent
        ? route('events.show', $featuredEvent).($isRabbaniyeen ? '#sajjil' : '')
        : route('events.index');
@endphp

@section('content')
    <section data-home-featured class="relative isolate overflow-hidden bg-[#123329] text-[#f7f1df]">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_88%_12%,rgba(52,211,153,0.12),transparent_36%)]"></div>
        <div class="mx-auto w-full max-w-7xl px-4 pb-12 pt-10 sm:px-6 sm:pb-16 sm:pt-14 lg:px-8 lg:py-20">
            <div class="mb-8 flex flex-wrap items-center justify-between gap-3 border-b border-white/15 pb-5 text-sm">
                <p class="font-bold tracking-wide text-[#a5dfca]">بِيرُحاء إبراء</p>
                <p class="text-[#f7f1df]/65">مؤسسة تربوية للفتيان من الصف السابع إلى الثاني عشر</p>
            </div>

            @if ($featuredEvent)
                <article class="grid items-center gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,0.95fr)] lg:gap-14">
                    <div class="relative order-1 overflow-hidden border border-white/15 bg-[#224539] lg:order-2">
                        <img src="{{ $featuredImage }}" alt="{{ $featuredEvent->name }}" width="1536" height="1024"
                            fetchpriority="high" class="aspect-[16/10] w-full object-cover lg:aspect-[5/4]">
                        <div class="pointer-events-none absolute inset-0 ring-1 ring-inset ring-white/10"></div>
                    </div>

                    <div class="order-2 max-w-2xl lg:order-1">
                        <p class="inline-flex items-center gap-2 text-sm font-bold text-[#a5dfca]">
                            <span class="size-2 rounded-full bg-[#a5dfca]"></span>
                            {{ $isRabbaniyeen ? 'تحفيظ القرآن للفتيان' : 'الفعالية القادمة' }}
                        </p>
                        <h1 class="mt-3 font-heading text-5xl font-bold leading-[1.2] sm:mt-5 sm:text-6xl lg:text-7xl">
                            {{ $featuredEvent->name }}
                        </h1>
                        <p class="mt-3 max-w-[48ch] text-lg leading-9 text-[#f7f1df]/82 sm:mt-5">
                            {{ $isRabbaniyeen ? 'وِردٌ يوميٌّ مع مشرفٍ يتابع ابنك، وليلتان في بيرحاء كل شهر.' : $featuredEvent->excerpt }}
                        </p>
                        <div class="mt-5 flex flex-wrap gap-x-6 gap-y-2 text-sm text-[#f7f1df]/66 sm:mt-7">
                            <span class="inline-flex items-center gap-2">
                                <x-hugeicon name="calendar-03" class="text-lg" />
                                {{ $featuredEvent->starts_at?->translatedFormat('j F Y') ?? 'يُعلن الموعد قريبًا' }}
                            </span>
                            @if ($featuredEvent->location)
                                <span class="inline-flex items-center gap-2">
                                    <x-hugeicon name="map-pin" class="text-lg" />
                                    {{ $featuredEvent->location }}
                                </span>
                            @endif
                        </div>
                        <a href="{{ $featuredActionUrl }}"
                            class="mt-6 inline-flex min-h-13 items-center justify-center gap-3 bg-[#f7f1df] px-7 py-3 text-base font-bold text-[#123329] transition-colors hover:bg-[#d9f3e8] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#f7f1df] sm:mt-9">
                            {{ $featuredActionLabel }}
                            <x-hugeicon name="arrow-left-02" class="text-xl" />
                        </a>
                    </div>
                </article>
            @else
                <div class="max-w-3xl py-14">
                    <p class="text-sm font-bold text-[#a5dfca]">قريبًا في بيرحاء</p>
                    <h1 class="mt-5 font-heading text-4xl font-bold leading-tight sm:text-6xl">نعدّ الفعالية القادمة</h1>
                    <p class="mt-5 text-lg leading-9 text-[#f7f1df]/78">ستظهر هنا بمجرد اعتماد موعدها وفتحها للجمهور.</p>
                    <a href="{{ route('events.index') }}"
                        class="mt-8 inline-flex min-h-12 items-center gap-3 bg-[#f7f1df] px-6 py-3 font-bold text-[#123329] transition-colors hover:bg-[#d9f3e8]">
                        تصفَّح البرامج
                        <x-hugeicon name="arrow-left-02" class="text-xl" />
                    </a>
                </div>
            @endif
        </div>
    </section>

    <section data-home-coffee class="bg-[#f6fbf8] py-14 sm:py-20 dark:bg-[#0a1914]">
        <div class="mx-auto grid w-full max-w-7xl items-center gap-8 px-4 sm:px-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] lg:gap-16 lg:px-8">
            <div class="max-w-xl">
                <p class="text-sm font-bold text-[#007a52] dark:text-[#8ad9b8]">المتجر / ٠٢</p>
                <h2 class="mt-4 font-heading text-4xl font-bold leading-tight text-[#123329] sm:text-5xl dark:text-[#f7f1df]">قهوة بيرحاء</h2>
                <p class="mt-5 text-lg leading-8 text-[#315e52] dark:text-[#d2e7df]/78">اطلب قهوتك من متجر بيرحاء الإلكتروني.</p>
                <a href="{{ route('coffee') }}"
                    class="mt-7 inline-flex min-h-12 items-center gap-3 border-b-2 border-[#007a52] pb-2 text-base font-bold text-[#007a52] transition-colors hover:border-[#123329] hover:text-[#123329] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#007a52] dark:border-[#8ad9b8] dark:text-[#8ad9b8] dark:hover:text-white">
                    اطلب الآن
                    <x-hugeicon name="arrow-left-02" class="text-xl" />
                </a>
            </div>
            <div class="overflow-hidden border border-[#2a8069]/15 bg-white dark:border-white/10">
                <img src="{{ asset('images/coffee-byruha-menu.webp') }}"
                    alt="قهوة بيرحاء ومخبوزاتها" width="1536" height="1024" loading="lazy"
                    class="aspect-[16/9] w-full object-cover sm:aspect-[2/1] lg:aspect-[16/9]">
            </div>
        </div>
    </section>

    <section data-home-events class="border-t border-[#2a8069]/14 bg-white py-14 sm:py-20 dark:border-white/10 dark:bg-[#07120f]">
        <div class="mx-auto grid w-full max-w-7xl items-end gap-8 px-4 sm:px-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:gap-12 lg:px-8">
            <div class="max-w-3xl">
                <p class="text-sm font-bold text-[#007a52] dark:text-[#8ad9b8]">للفتيان وآبائهم / ٠٣</p>
                <h2 class="mt-4 font-heading text-4xl font-bold leading-tight text-[#123329] sm:text-5xl dark:text-[#f7f1df]">فعاليات بيرحاء</h2>
                <p class="mt-5 text-lg leading-8 text-[#315e52] dark:text-[#d2e7df]/78">مخيمات ورحلات ولقاءات تربوية، من ساعةٍ إلى شهر.</p>
            </div>
            <a href="{{ route('events.index') }}" data-home-events-link
                class="inline-flex min-h-13 w-fit items-center justify-center gap-3 bg-[#007a52] px-7 py-3 text-base font-bold text-white transition-colors hover:bg-[#006746] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#007a52]">
                تصفَّح البرامج
                <x-hugeicon name="arrow-left-02" class="text-xl" />
            </a>
        </div>
    </section>
@endsection
