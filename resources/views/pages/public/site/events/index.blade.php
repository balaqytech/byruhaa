@extends('layouts.public')

@section('content')
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

        <div class="mt-12 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($events as $event)
                <article
                    class="public-card group flex min-h-[25rem] flex-col overflow-hidden border border-[#009060]/12 bg-white/66 p-5 shadow-sm shadow-[#123329]/5 transition duration-300 hover:-translate-y-1 hover:border-[#009060]/26 hover:bg-white dark:border-white/10 dark:bg-white/8 dark:shadow-black/20 dark:hover:border-[#e0a800]/28 dark:hover:bg-white/12">
                    <div class="flex items-start justify-between gap-4">
                        <span
                            class="inline-flex items-center gap-2 rounded-sm bg-[#e01838]/8 px-3 py-1.5 text-xs font-semibold text-[#e01838] dark:bg-[#e01838]/14 dark:text-[#ff7487]">
                            <x-hugeicon name="ticket-01" class="text-base" />
                            {{ $event->type->getLabel() }}
                        </span>
                    </div>

                    <div class="mt-8 flex flex-1 flex-col">
                        <h2 class="font-heading text-3xl font-bold leading-tight text-[#123329] dark:text-[#f7f1df]">
                            {{ $event->name }}
                        </h2>

                        @if ($event->excerpt)
                            <p class="mt-4 line-clamp-3 text-sm leading-7 text-[#123329]/64 dark:text-[#f7f1df]/64">
                                {{ $event->excerpt }}
                            </p>
                        @endif

                        <div class="mt-8 grid gap-3 text-sm text-[#123329]/66 dark:text-[#f7f1df]/66">
                            @if ($event->starts_at)
                                <div class="flex items-center gap-3">
                                    <x-hugeicon name="clock-01" class="text-lg text-[#e0a800]" />
                                    <span dir="ltr">{{ $event->starts_at->format('Y-m-d') }}</span>
                                </div>
                            @endif

                            @if ($event->location)
                                <div class="flex items-center gap-3">
                                    <x-hugeicon name="map-pin" class="text-lg text-[#30b070]" />
                                    <span>{{ $event->location }}</span>
                                </div>
                            @endif

                            @unless ($event->isComingSoon())
                                <div class="flex items-center gap-3">
                                    <x-hugeicon name="wallet-02" class="text-lg text-[#1898b0]" />
                                    <x-money :amount-baisa="$event->price_baisa" :currency="$event->currency" />
                                </div>
                            @endunless
                        </div>

                        @if (Route::has('events.show'))
                            <div class="mt-auto pt-8">
                                <a href="{{ route('events.show', $event) }}"
                                    class="inline-flex min-h-11 items-center gap-2 rounded-sm border border-[#009060]/18 px-4 py-2 text-sm font-bold text-[#009060] transition hover:border-[#009060]/35 hover:bg-[#009060]/8 dark:border-[#e0a800]/24 dark:text-[#e0a800] dark:hover:bg-[#e0a800]/10">
                                    <span>تفاصيل الفعالية</span>
                                    <x-hugeicon name="arrow-left-02" class="text-lg" />
                                </a>
                            </div>
                        @endif
                    </div>
                </article>
            @empty
                <div
                    class="public-card border border-dashed border-[#009060]/24 bg-white/66 px-6 py-16 text-center md:col-span-2 xl:col-span-3 dark:border-[#e0a800]/24 dark:bg-white/8">
                    <x-hugeicon name="calendar-remove-01" class="mx-auto text-4xl text-[#e0a800]" />
                    <h2 class="mt-5 font-heading text-3xl font-bold text-[#123329] dark:text-[#f7f1df]">لا توجد فعاليات
                        منشورة حالياً</h2>
                    <p class="mx-auto mt-3 max-w-xl text-sm leading-7 text-[#123329]/64 dark:text-[#f7f1df]/64">
                        نعمل على تجهيز برنامج يليق بتجربة بيرحاء. ستظهر الفعاليات هنا فور فتح باب الحجز.
                    </p>
                </div>
            @endforelse
        </div>
    </section>
@endsection
