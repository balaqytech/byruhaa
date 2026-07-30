@extends('layouts.public', [
    'title' => $title,
    'metaDescription' => $metaDescription,
    'metaImage' => $metaImage,
])

@php
    $facilityIcons = ['home-01', 'check-list', 'user-group', 'sparkles', 'ticket-01', 'map-pin'];
@endphp

@section('content')
    <section class="relative isolate overflow-hidden">
        <div
            class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_82%_18%,rgba(0,144,96,0.16),transparent_34%),radial-gradient(circle_at_14%_80%,rgba(224,168,0,0.10),transparent_30%)] dark:bg-[radial-gradient(circle_at_82%_18%,rgba(52,211,153,0.10),transparent_34%),radial-gradient(circle_at_14%_80%,rgba(224,168,0,0.07),transparent_30%)]">
        </div>

        <div
            class="mx-auto grid min-h-[calc(100dvh-5rem)] w-full max-w-7xl items-center gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,0.86fr)_minmax(440px,1.14fr)] lg:px-8 lg:py-16">
            <div class="public-hero-copy max-w-2xl">
                <p
                    class="inline-flex min-h-10 items-center gap-2 rounded-sm border border-[#009060]/20 bg-white/72 px-3 py-2 text-sm font-bold text-[#007a52] shadow-sm shadow-[#123329]/5 dark:border-[#34d399]/20 dark:bg-white/6 dark:text-[#6ee7b7]">
                    <x-hugeicon name="information-circle" class="text-lg" />
                    {{ $about->eyebrow }}
                </p>

                <h1
                    class="mt-5 max-w-2xl font-heading text-4xl font-bold leading-[1.2] text-[#123329] sm:text-5xl lg:text-6xl dark:text-[#f7f1df]">
                    {{ $about->page_title }}
                </h1>

                <p class="mt-6 max-w-[55ch] text-lg leading-8 text-[#315e52] dark:text-[#d2e7df]/80">
                    {{ $about->hero_summary }}
                </p>

                <a href="#about-details"
                    class="mt-8 inline-flex min-h-12 items-center justify-center gap-2 whitespace-nowrap rounded-sm bg-[#007a52] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#007a52]/16 transition hover:-translate-y-0.5 hover:bg-[#006746] active:translate-y-px motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                    <span>اكتشف المكان</span>
                    <x-hugeicon name="arrow-left-02" class="text-lg" />
                </a>
            </div>

            <div class="relative mx-auto w-full max-w-3xl lg:mx-0">
                @if ($heroImage)
                    <figure
                        class="overflow-hidden rounded-sm border border-[#2a8069]/14 bg-white shadow-[0_32px_90px_rgba(18,51,41,0.16)] dark:border-white/10 dark:bg-white/5 dark:shadow-black/30">
                        <img src="{{ $heroImage['url'] }}" alt="{{ $heroImage['alt'] }}" width="1200" height="900"
                            fetchpriority="high" class="aspect-[4/3] h-full w-full object-cover">
                    </figure>
                @else
                    <div
                        class="grid min-h-[20rem] content-between overflow-hidden rounded-sm border border-[#2a8069]/14 bg-[#123329] p-7 text-white shadow-[0_32px_90px_rgba(18,51,41,0.16)] sm:min-h-[26rem] sm:p-10 dark:border-white/10 dark:bg-[#0c1e19] dark:shadow-black/30">
                        <div class="flex items-start justify-between gap-6">
                            <x-hugeicon name="map-pin" class="text-3xl text-[#dfb458]" />
                            <p class="max-w-[18ch] text-sm leading-7 text-white/68">ولاية إبراء، محافظة شمال الشرقية</p>
                        </div>
                        <div>
                            <p class="font-heading text-7xl font-bold leading-none text-[#f5d478] sm:text-8xl">+10,000</p>
                            <p class="mt-4 text-lg font-semibold text-white/80">متر مربع للتعلّم والإقامة والتجربة</p>
                        </div>
                    </div>
                @endif

                <dl
                    class="me-4 -mt-6 grid max-w-2xl grid-cols-3 divide-x-reverse divide-x divide-[#2a8069]/12 rounded-sm border border-[#2a8069]/14 bg-[#f6fbf8] shadow-xl shadow-[#123329]/10 dark:divide-white/10 dark:border-white/10 dark:bg-[#10251f] sm:me-auto sm:-mt-9">
                    @foreach ($about->highlights as $highlight)
                        <div class="min-w-0 px-3 py-4 text-center sm:px-5">
                            <dt class="truncate text-xs text-[#315e52]/72 dark:text-[#d2e7df]/62">{{ $highlight['label'] }}</dt>
                            <dd class="mt-1 font-heading text-xl font-bold text-[#007a52] sm:text-2xl dark:text-[#f5d478]">{{ $highlight['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </section>

    <section id="about-details" class="scroll-mt-24 border-y border-[#2a8069]/12 bg-white/62 dark:border-white/10 dark:bg-white/[0.03]">
        <div
            class="mx-auto grid w-full max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[minmax(0,0.72fr)_minmax(0,1.28fr)] lg:gap-20 lg:px-8 lg:py-24">
            <div>
                <p class="text-sm font-bold text-[#007a52] dark:text-[#6ee7b7]">في قلب الشرقية</p>
                <h2 class="mt-3 font-heading text-3xl font-bold leading-tight text-[#123329] lg:text-5xl dark:text-[#f7f1df]">
                    {{ $about->intro_heading }}
                </h2>
            </div>
            <p class="max-w-[70ch] text-lg leading-9 text-[#315e52] dark:text-[#d2e7df]/78">
                {{ $about->intro_body }}
            </p>
        </div>
    </section>

    <section>
        <div class="mx-auto w-full max-w-7xl px-4 py-18 sm:px-6 lg:px-8 lg:py-24">
            <div class="max-w-3xl">
                <p class="text-sm font-bold text-[#007a52] dark:text-[#6ee7b7]">ما يحويه المخيم</p>
                <h2 class="mt-3 font-heading text-3xl font-bold leading-tight text-[#123329] lg:text-5xl dark:text-[#f7f1df]">
                    {{ $about->facilities_heading }}
                </h2>
            </div>

            <div class="mt-12 grid border-b border-[#2a8069]/14 md:grid-cols-2 dark:border-white/10">
                @foreach ($about->facilities as $facility)
                    <article
                        class="group grid grid-cols-[auto_minmax(0,1fr)] gap-5 border-t border-[#2a8069]/14 py-7 md:odd:pe-8 md:even:border-s md:even:ps-8 dark:border-white/10">
                        <span
                            class="flex size-11 items-center justify-center rounded-sm bg-[#007a52]/9 text-[#007a52] transition group-hover:bg-[#007a52] group-hover:text-white dark:bg-[#6ee7b7]/10 dark:text-[#6ee7b7] dark:group-hover:bg-[#6ee7b7] dark:group-hover:text-[#07120f]">
                            <x-hugeicon :name="$facilityIcons[$loop->index % count($facilityIcons)]" class="text-xl" />
                        </span>
                        <div>
                            <h3 class="font-heading text-xl font-bold text-[#123329] dark:text-[#f7f1df]">{{ $facility['title'] }}</h3>
                            <p class="mt-2 leading-7 text-[#315e52] dark:text-[#d2e7df]/72">{{ $facility['description'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="border-y border-[#2a8069]/12 bg-[#123329] text-white dark:border-white/10 dark:bg-[#0c1e19]">
        <div class="mx-auto grid w-full max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] lg:px-8 lg:py-24">
            <div class="max-w-lg">
                <p class="text-sm font-bold text-[#87d9bd]">أكثر من موقع إقامة</p>
                <h2 class="mt-3 font-heading text-3xl font-bold leading-tight lg:text-5xl">{{ $about->advantages_heading }}</h2>
                <p class="mt-5 leading-8 text-white/68">صُممت المرافق لتخدم البرنامج التربوي، لا لتكون منفصلة عنه.</p>
            </div>

            <div class="grid gap-7 sm:grid-cols-2">
                @foreach ($about->advantages as $advantage)
                    <article class="border-t border-white/18 pt-5">
                        <x-hugeicon name="checkmark-circle-01" class="text-2xl text-[#f5d478]" />
                        <h3 class="mt-4 font-heading text-xl font-bold">{{ $advantage['title'] }}</h3>
                        <p class="mt-2 leading-7 text-white/68">{{ $advantage['description'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    @if ($galleryImages !== [])
        <section>
            <div class="mx-auto w-full max-w-7xl px-4 py-18 sm:px-6 lg:px-8 lg:py-24">
                <div class="max-w-3xl">
                    <p class="text-sm font-bold text-[#007a52] dark:text-[#6ee7b7]">صور حقيقية من المكان</p>
                    <h2 class="mt-3 font-heading text-3xl font-bold text-[#123329] lg:text-5xl dark:text-[#f7f1df]">{{ $about->gallery_heading }}</h2>
                    <p class="mt-4 max-w-[62ch] leading-8 text-[#315e52] dark:text-[#d2e7df]/76">{{ $about->gallery_intro }}</p>
                </div>

                <div class="mt-10 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($galleryImages as $image)
                        <figure @class([
                            'overflow-hidden rounded-sm border border-[#2a8069]/14 bg-white dark:border-white/10 dark:bg-white/5',
                            'sm:col-span-2 lg:row-span-2' => $loop->first && count($galleryImages) > 1,
                        ])>
                            <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" width="900" height="700" loading="lazy"
                                @class([
                                    'h-full w-full object-cover',
                                    'aspect-[4/3] lg:min-h-[32rem]' => $loop->first && count($galleryImages) > 1,
                                    'aspect-[4/3]' => ! $loop->first || count($galleryImages) === 1,
                                ])>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="border-t border-[#2a8069]/12 bg-white/62 dark:border-white/10 dark:bg-white/[0.03]">
        <div
            class="mx-auto grid w-full max-w-7xl gap-8 px-4 py-14 sm:px-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end lg:px-8 lg:py-16">
            <div class="max-w-3xl">
                <h2 class="font-heading text-3xl font-bold text-[#123329] lg:text-4xl dark:text-[#f7f1df]">هل تريد زيارة بيرحاء؟</h2>
                <p class="mt-4 leading-8 text-[#315e52] dark:text-[#d2e7df]/76">اعرف تفاصيل الموقع والزيارة، أو ابدأ مباشرة مع مساعد بيرحاء الذكي.</p>
            </div>
            <a href="{{ route('contact') }}"
                class="inline-flex min-h-12 items-center justify-center gap-2 whitespace-nowrap rounded-sm bg-[#007a52] px-6 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-[#006746] active:translate-y-px motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                <span>تواصل معنا</span>
                <x-hugeicon name="arrow-left-02" class="text-lg" />
            </a>
        </div>
    </section>
@endsection
