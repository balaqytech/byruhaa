@extends('layouts.public', [
    'title' => $title,
    'metaDescription' => $metaDescription,
])

@php
    $destinations = [
        ['key' => 'rabbaniyeen', 'title' => 'برنامج ربانيين', 'description' => 'برنامج فريد لحفظ القرآن للفتيان من الصف ٧ إلى ١٢، على أيدي خبراء', 'credit' => 'بإشراف عبدالله العيسري', 'url' => $rabbaniyeen ? route('events.show', $rabbaniyeen) : route('events.index'), 'icon' => 'book-open-text'],
        ['key' => 'coffee', 'title' => 'قهوة بيرحاء', 'description' => 'أول قهوة مختصة صُمّمت للفتيان من ١٣ إلى ١٨ وفق قيم تربوية رفيعة', 'credit' => 'بقيادة عبدالله العيسري', 'url' => route('coffee'), 'icon' => 'coffee'],
        ['key' => 'events', 'title' => 'فعاليات بيرحاء', 'description' => 'فعاليات تغطي كل قيم خماسية السكينة في بيئة آمنة', 'credit' => 'بقيادة عبدالله العيسري', 'url' => route('events.index'), 'icon' => 'calendar-03'],
    ];
    $assistantUrl = 'https://wa.me/'.config('coffee.whatsapp_number').'?text='.rawurlencode('السلام عليكم، أحتاج مساعدة لاختيار وجهتي في بيرحاء.');
@endphp

@section('content')
    <section aria-labelledby="destinations-title" class="relative isolate overflow-hidden">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_at_80%_10%,rgba(0,144,96,0.13),transparent_55%)] dark:bg-[radial-gradient(ellipse_at_80%_10%,rgba(52,211,153,0.08),transparent_55%)]" aria-hidden="true"></div>
        <div class="mx-auto flex min-h-[calc(100svh-10rem)] max-w-7xl flex-col justify-center px-4 py-3 sm:px-6 lg:min-h-[calc(100svh-5rem)] lg:px-8 lg:py-20">
            <header class="mb-4 max-w-2xl lg:mb-12">
                <p class="mb-2 flex items-center gap-3 text-xs lg:mb-4 lg:text-sm font-bold text-[#2a8069] dark:text-[#a8d9ca]">
                    <span class="h-px w-8 bg-[#b8893a]" aria-hidden="true"></span>
                    إبراء، سلطنة عُمان
                </p>
                <h1 id="destinations-title" class="font-heading text-[28px] font-black leading-tight tracking-tight text-[#123329] sm:text-5xl lg:text-6xl dark:text-[#f7f1df]">اختر وجهتك</h1>
            </header>

            <div class="grid gap-3 lg:grid-cols-3 lg:gap-6">
                @foreach ($destinations as $destination)
                    <a href="{{ $destination['url'] }}" data-home-destination="{{ $destination['key'] }}"
                        class="group relative grid grid-cols-[48px_minmax(0,1fr)] items-center gap-x-3 rounded-2xl lg:flex lg:flex-col lg:items-stretch lg:gap-x-0 border border-[#2a8069]/20 bg-white/90 p-3 shadow-[0_8px_28px_rgba(18,51,41,0.04)] transition-[background-color,border-color,box-shadow] duration-200 hover:border-[#009060]/50 hover:bg-white hover:shadow-[0_16px_36px_rgba(18,51,41,0.09)] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#009060] lg:p-8 dark:border-white/15 dark:bg-[#10241e]/90 dark:hover:border-[#6fbd9f]/50 dark:hover:bg-[#142d25]">
                        <div class="col-start-1 row-span-3 row-start-1 flex items-center justify-between lg:mb-6 lg:gap-4">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-[#dff6ef] text-2xl lg:size-16 lg:text-[32px] text-[#137051] dark:bg-[#1c3c30] dark:text-[#a8d9ca]" aria-hidden="true">
                                @if ($destination['icon'] === 'coffee')
                                    <svg viewBox="0 0 40 40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-6 lg:size-8">
                                        <path d="M7 18h22v8a9 9 0 0 1-9 9h-4a9 9 0 0 1-9-9zM29 20h3a5 5 0 0 1 0 10h-4M12 6c-2 3 2 4 0 7M18 6c-2 3 2 4 0 7M24 6c-2 3 2 4 0 7" />
                                    </svg>
                                @else
                                    <x-hugeicon :name="$destination['icon']" />
                                @endif
                            </span>
                            <span class="hidden lg:block" aria-hidden="true">
                                <x-hugeicon name="arrow-left-02" class="text-2xl text-[#b8893a]" />
                            </span>
                        </div>
                        <h2 class="col-start-2 row-start-1 mb-1 font-heading text-[22px] font-bold leading-snug lg:mb-3 lg:text-[28px] text-[#123329] lg:text-3xl dark:text-[#f7f1df]">{{ $destination['title'] }}</h2>
                        <p class="col-start-2 row-start-2 mb-2 text-sm leading-6 lg:mb-7 lg:text-base lg:leading-8 text-[#526c61] lg:text-lg dark:text-[#b6cbbf]">{{ $destination['description'] }}</p>
                        <p class="col-start-2 row-start-3 text-xs lg:mt-auto lg:border-t lg:border-[#2a8069]/15 lg:pt-5 lg:text-sm font-bold text-[#2a8069] dark:border-white/10 dark:text-[#a8d9ca]">{{ $destination['credit'] }}</p>
                    </a>
                @endforeach
            </div>

            <p class="mt-3 text-xs leading-6 lg:mt-8 lg:text-sm lg:leading-7 text-[#526c61] sm:text-base dark:text-[#b6cbbf]">
                هل تحتاج إلى عون؟
                <a href="{{ $assistantUrl }}" class="rounded font-bold text-[#137051] underline decoration-[#b8893a]/60 underline-offset-4 hover:decoration-[#137051] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#009060] dark:text-[#a8d9ca]">اسأل مساعد بيرحاء.</a>
            </p>
        </div>
    </section>
@endsection
