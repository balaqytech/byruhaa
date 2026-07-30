@extends('layouts.public', [
    'title' => $title,
    'metaDescription' => $metaDescription,
])

@php
    $featuredImage = match ($featuredEvent?->landing_page_key) {
        'umrah-2026-v1' => asset('images/umrah-2026-hero.png'),
        'life-after-school-v1' => asset('images/after-twelfth-omani-graduate-hero.png'),
        default => asset('images/after-twelfth-plan-workshop.png'),
    };
@endphp

@section('content')
    @push('home-after-events')
    <section class="relative isolate overflow-hidden">
        <div
            class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_14%_18%,rgba(0,144,96,0.16),transparent_30%),radial-gradient(circle_at_88%_82%,rgba(42,128,105,0.10),transparent_34%)] dark:bg-[radial-gradient(circle_at_14%_18%,rgba(52,211,153,0.12),transparent_30%),radial-gradient(circle_at_88%_82%,rgba(42,128,105,0.12),transparent_34%)]">
        </div>

        <div
            class="mx-auto grid min-h-[calc(100dvh-5rem)] w-full max-w-7xl items-center gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,0.88fr)_minmax(480px,1.12fr)] lg:px-8 lg:py-16">
            <div class="public-hero-copy max-w-3xl">
                <p
                    class="inline-flex min-h-10 items-center gap-2 rounded-sm border border-[#009060]/20 bg-white/72 px-3 py-2 text-sm font-bold text-[#007a52] shadow-sm shadow-[#123329]/5 dark:border-[#34d399]/20 dark:bg-white/6 dark:text-[#6ee7b7]">
                    <x-hugeicon name="map-pin" class="text-lg" />
                    بيرحاء إبراء
                </p>

                <h1
                    class="mt-5 max-w-3xl font-heading text-4xl font-bold leading-[1.2] text-[#123329] sm:text-5xl lg:text-6xl dark:text-[#f7f1df]">
                    مساحةٌ ينضج فيها الفتى بالفعل
                </h1>

                <p class="mt-6 max-w-[58ch] text-lg leading-8 text-[#315e52] dark:text-[#d2e7df]/80">
                    برامج تربوية ورحلات ومخيمات تجمع العبادة والعلم والمسؤولية والصحبة الطيبة في تجربة واحدة.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('events.index') }}"
                        class="inline-flex min-h-12 items-center justify-center gap-2 whitespace-nowrap rounded-sm bg-[#007a52] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#007a52]/16 transition hover:-translate-y-0.5 hover:bg-[#006746] active:translate-y-px motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                        <span>استكشف الفعاليات</span>
                        <x-hugeicon name="arrow-left-02" class="text-lg" />
                    </a>
                    <a href="{{ route('about') }}"
                        class="inline-flex min-h-12 items-center justify-center gap-2 whitespace-nowrap rounded-sm border border-[#123329]/18 bg-white/68 px-6 py-3 text-sm font-bold text-[#123329] transition hover:-translate-y-0.5 hover:border-[#007a52]/38 hover:text-[#007a52] active:translate-y-px dark:border-white/16 dark:bg-white/5 dark:text-[#f7f1df] dark:hover:border-[#6ee7b7]/36 dark:hover:text-[#6ee7b7] motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                        <span>تعرّف إلى بيرحاء</span>
                        <x-hugeicon name="information-circle" class="text-lg" />
                    </a>
                </div>
            </div>

            <div class="relative mx-auto w-full max-w-2xl lg:mx-0">
                <div
                    class="overflow-hidden rounded-sm border border-[#2a8069]/14 bg-white shadow-[0_32px_90px_rgba(18,51,41,0.16)] dark:border-white/10 dark:bg-white/5 dark:shadow-black/30">
                    <img src="{{ asset('images/after-twelfth-plan-workshop.png') }}"
                        alt="فتيان عُمانيون يعملون مع مرشد على خطة عملية" width="1536" height="1024"
                        fetchpriority="high" class="aspect-[3/2] h-full w-full object-cover">
                </div>
                <div
                    class="mt-4 grid grid-cols-[0.72fr_1.28fr] items-end gap-4 sm:ms-12 lg:-mt-20 lg:ms-10 lg:me-[-2rem]">
                    <div class="overflow-hidden rounded-sm border-4 border-[#f6fbf8] shadow-xl shadow-[#123329]/12 dark:border-[#07120f]">
                        <img src="{{ asset('images/after-twelfth-mentor-circle.png') }}"
                            alt="جلسة إرشاد تربوية تحفظ خصوصية المشاركين" width="864" height="1821" loading="eager"
                            class="aspect-[4/5] h-full w-full object-cover">
                    </div>
                    <blockquote
                        class="border-s-2 border-[#007a52] py-2 ps-5 text-lg font-semibold leading-8 text-[#123329] dark:border-[#6ee7b7] dark:text-[#f7f1df]">
                        لا نكتفي بأن يعرف الفتى الصواب، بل نهيّئ له أن يعيشه ويختاره.
                    </blockquote>
                </div>
            </div>
        </div>
    </section>

    <section class="border-y border-[#2a8069]/12 bg-white/62 dark:border-white/10 dark:bg-white/[0.03]">
        <div class="mx-auto grid w-full max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[0.72fr_1.28fr] lg:px-8 lg:py-20">
            <div>
                <h2 class="font-heading text-3xl font-bold leading-tight text-[#123329] lg:text-4xl dark:text-[#f7f1df]">
                    يومٌ متوازن، وأثرٌ يمتد
                </h2>
                <p class="mt-4 max-w-[52ch] leading-8 text-[#315e52] dark:text-[#d2e7df]/76">
                    نصمم التجربة حول الفتى كاملًا، فلا ينفصل ما يتعلمه عما يفعله، ولا تنتهي الصلة بانتهاء البرنامج.
                </p>
            </div>

            <div class="grid gap-x-8 gap-y-7 sm:grid-cols-2">
                @foreach ([
                    ['icon' => 'book-open-text', 'title' => 'عبادة وعلم', 'body' => 'معنى واضح، وممارسة هادئة، ومساحة آمنة للسؤال.'],
                    ['icon' => 'checkmark-badge-01', 'title' => 'عمل ومسؤولية', 'body' => 'مهمات حقيقية يتعلم فيها الفتى أن يخدم ويقرر ويتحمل.'],
                    ['icon' => 'user-group', 'title' => 'حركة وصحبة', 'body' => 'لعب وتحديات وصحبة صالحة تصنع الألفة من دون تكلف.'],
                    ['icon' => 'clock-01', 'title' => 'متابعة بعدية', 'body' => 'ما يبدأ في المخيم يعود مع الفتى إلى بيته ومدرسته وحياته.'],
                ] as $pillar)
                    <article class="public-card border-t border-[#2a8069]/18 pt-5 dark:border-white/12">
                        <div class="flex items-start gap-4">
                            <span
                                class="flex size-11 shrink-0 items-center justify-center rounded-sm bg-[#007a52]/10 text-[#007a52] dark:bg-[#6ee7b7]/10 dark:text-[#6ee7b7]">
                                <x-hugeicon :name="$pillar['icon']" class="text-xl" />
                            </span>
                            <div>
                                <h3 class="font-heading text-xl font-bold text-[#123329] dark:text-[#f7f1df]">
                                    {{ $pillar['title'] }}</h3>
                                <p class="mt-2 leading-7 text-[#315e52] dark:text-[#d2e7df]/72">{{ $pillar['body'] }}</p>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto w-full max-w-7xl px-4 py-18 sm:px-6 lg:px-8 lg:py-24">
        <div
            class="grid overflow-hidden rounded-sm border border-[#2a8069]/14 bg-white/72 shadow-[0_24px_70px_rgba(18,51,41,0.10)] lg:grid-cols-[minmax(0,1.08fr)_minmax(0,0.92fr)] dark:border-white/10 dark:bg-white/5 dark:shadow-black/24">
            <div class="relative min-h-72 overflow-hidden lg:min-h-[28rem]">
                <img src="{{ asset('images/coffee-byruha-menu.webp') }}"
                    alt="مشروبات قهوة ومخبوزات من القائمة الافتتاحية لقهوة بيرحاء" width="1536" height="1024"
                    loading="lazy" class="absolute inset-0 h-full w-full object-cover">
            </div>
            <div class="flex flex-col justify-center p-7 sm:p-10 lg:p-12">
                <h2 class="font-heading text-3xl font-bold leading-tight text-[#123329] lg:text-5xl dark:text-[#f7f1df]">
                    قهوة بيرحاء، بابٌ يومي للمكان
                </h2>
                <p class="mt-5 max-w-xl leading-8 text-[#315e52] dark:text-[#d2e7df]/76">
                    قهوة مختصة ومخبوزات خفيفة للزائر والفتى وأسرته، بقائمة واضحة والبيع من مخيم بيرحاء في إبراء.
                </p>
                <a href="{{ route('coffee') }}"
                    class="mt-7 inline-flex min-h-12 w-fit items-center justify-center gap-2 whitespace-nowrap rounded-sm bg-[#007a52] px-6 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-[#006746] active:translate-y-px motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                    <span>استكشف قهوة بيرحاء</span>
                    <x-hugeicon name="arrow-left-02" class="text-lg" />
                </a>
            </div>
        </div>
    </section>
    @endpush

    <section class="mx-auto w-full max-w-7xl px-4 py-18 sm:px-6 lg:px-8 lg:py-24">
        @if ($featuredEvent)
            <article
                class="public-card overflow-hidden rounded-sm border border-[#2a8069]/14 bg-[#0d2b25] text-white shadow-[0_26px_80px_rgba(18,51,41,0.16)] dark:border-white/10">
                <div class="grid lg:grid-cols-[minmax(0,0.94fr)_minmax(0,1.06fr)]">
                    <div class="order-1 relative min-h-80 overflow-hidden lg:order-2 lg:min-h-[34rem]">
                        <img src="{{ $featuredImage }}" alt="{{ $featuredEvent->name }}" width="1536" height="1024"
                            loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                        <div class="absolute inset-0 bg-[linear-gradient(90deg,rgba(13,43,37,0.05),rgba(13,43,37,0.24))]"></div>
                    </div>

                    <div class="order-2 flex flex-col justify-center p-7 sm:p-10 lg:order-1 lg:p-14">
                        <p class="text-sm font-bold text-[#87d9bd]">الفعالية الأقرب</p>
                        <h2 class="mt-3 font-heading text-3xl font-bold leading-tight sm:text-4xl lg:text-5xl">
                            {{ $featuredEvent->name }}
                        </h2>
                        <p class="mt-5 max-w-xl text-base leading-8 text-white/76">
                            {{ $featuredEvent->excerpt }}
                        </p>

                        <dl class="mt-8 grid gap-5 border-t border-white/14 pt-7 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm text-white/58">الموعد</dt>
                                <dd class="mt-1 font-bold">
                                    {{ $featuredEvent->starts_at?->translatedFormat('j F Y') ?? 'يُعلن قريبًا' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm text-white/58">الفئة العمرية</dt>
                                <dd class="mt-1 font-bold">{{ $featuredEvent->minimum_age }} إلى
                                    {{ $featuredEvent->maximum_age }} سنة</dd>
                            </div>
                        </dl>

                        @if ($featuredTierOffer['current'])
                            <div class="mt-7 grid gap-3 sm:grid-cols-[1.15fr_0.85fr]" data-featured-tier-offer>
                                <div class="border-s-4 border-[#87d9bd] bg-white/10 p-5">
                                    <p class="text-sm font-bold text-[#87d9bd]">الباقة المتاحة الآن</p>
                                    <p class="mt-2 font-heading text-3xl font-bold">{{ $featuredTierOffer['current']['name'] }}</p>
                                    <div class="mt-4 flex flex-wrap items-end justify-between gap-3">
                                        <p class="font-heading text-5xl font-bold text-white"><x-money :amount-baisa="$featuredTierOffer['current']['price_baisa']"
                                                :currency="$featuredTierOffer['current']['currency']" /></p>
                                        <p class="text-lg font-bold text-[#87d9bd]">بقي {{ $featuredTierOffer['current']['remaining_seats'] }} مقاعد</p>
                                    </div>
                                </div>
                                @if ($featuredTierOffer['next'])
                                    <div class="bg-white/6 p-5">
                                        <p class="text-sm font-bold text-white/62">الباقة التالية</p>
                                        <p class="mt-2 font-heading text-2xl font-bold">{{ $featuredTierOffer['next']['name'] }}</p>
                                        <p class="mt-4 font-heading text-3xl font-bold text-[#f4dfb2]"><x-money :amount-baisa="$featuredTierOffer['next']['price_baisa']"
                                                :currency="$featuredTierOffer['next']['currency']" /></p>
                                    </div>
                                @endif
                            </div>
                        @elseif ($featuredTierOffer['is_sold_out'])
                            <p class="mt-7 border-s-4 border-[#f4dfb2] bg-white/8 p-5 font-heading text-2xl font-bold">اكتملت المقاعد</p>
                        @endif

                        <div class="mt-8 flex flex-wrap gap-3">
                            <a href="{{ route('events.show', $featuredEvent) }}"
                                class="inline-flex min-h-12 items-center justify-center gap-2 whitespace-nowrap rounded-sm bg-white px-6 py-3 text-sm font-bold text-[#0d2b25] transition hover:-translate-y-0.5 hover:bg-[#e9f7f1] active:translate-y-px motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                                <span>تفاصيل الفعالية</span>
                                <x-hugeicon name="arrow-left-02" class="text-lg" />
                            </a>
                            <a href="{{ route('customer.events.show', $featuredEvent) }}"
                                class="inline-flex min-h-12 items-center justify-center gap-2 whitespace-nowrap rounded-sm border border-white/24 px-6 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-white/10 active:translate-y-px motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                                <span>{{ $featuredEvent->canBook() ? 'احجز مقعدًا' : ($featuredEvent->canExpressInterest() ? 'أبدِ اهتمامك' : 'عرض الحالة') }}</span>
                                <x-hugeicon name="check-list" class="text-lg" />
                            </a>
                        </div>
                    </div>
                </div>
            </article>
        @else
            <div class="rounded-sm border border-dashed border-[#2a8069]/24 bg-white/54 p-10 text-center dark:border-white/14 dark:bg-white/[0.03]">
                <h2 class="font-heading text-3xl font-bold text-[#123329] dark:text-[#f7f1df]">نعدّ الفعالية القادمة</h2>
                <p class="mx-auto mt-3 max-w-xl leading-8 text-[#315e52] dark:text-[#d2e7df]/72">ستظهر هنا بمجرد اعتماد موعدها وفتحها للجمهور.</p>
            </div>
        @endif
    </section>

    @if ($upcomingEvents->isNotEmpty())
        <section class="border-y border-[#2a8069]/12 bg-white/62 dark:border-white/10 dark:bg-white/[0.03]">
            <div class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-20">
                <h2 class="font-heading text-3xl font-bold text-[#123329] lg:text-4xl dark:text-[#f7f1df]">فعاليات أخرى</h2>
                <div class="mt-9 grid gap-5 md:grid-cols-2">
                    @foreach ($upcomingEvents as $event)
                        <article
                            class="public-card grid gap-5 rounded-sm border border-[#2a8069]/14 bg-white/72 p-6 sm:grid-cols-[1fr_auto] sm:items-end dark:border-white/10 dark:bg-white/5">
                            <div>
                                <p class="text-sm font-bold text-[#007a52] dark:text-[#6ee7b7]">{{ $event->type->getLabel() }}</p>
                                <h3 class="mt-2 font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">{{ $event->name }}</h3>
                                <p class="mt-3 line-clamp-2 leading-7 text-[#315e52] dark:text-[#d2e7df]/72">{{ $event->excerpt }}</p>
                            </div>
                            <a href="{{ route('events.show', $event) }}"
                                class="inline-flex min-h-11 items-center justify-center gap-2 whitespace-nowrap rounded-sm border border-[#007a52]/22 px-4 py-2 text-sm font-bold text-[#007a52] transition hover:bg-[#007a52] hover:text-white dark:border-[#6ee7b7]/24 dark:text-[#6ee7b7] dark:hover:bg-[#6ee7b7] dark:hover:text-[#07120f]">
                                تفاصيل الفعالية
                            </a>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @stack('home-after-events')

    <section class="mx-auto grid w-full max-w-7xl gap-10 px-4 py-18 sm:px-6 lg:grid-cols-[0.82fr_1.18fr] lg:items-center lg:px-8 lg:py-24">
        <div class="overflow-hidden rounded-sm border border-[#2a8069]/14 bg-white dark:border-white/10 dark:bg-white/5">
            <img src="{{ asset('images/after-twelfth-mentor-circle.png') }}"
                alt="مرشد يستمع إلى فتيين في جلسة تربوية" width="864" height="1821" loading="lazy"
                class="aspect-[4/5] h-full max-h-[38rem] w-full object-cover">
        </div>
        <div class="max-w-2xl">
            <h2 class="font-heading text-3xl font-bold leading-tight text-[#123329] lg:text-5xl dark:text-[#f7f1df]">
                التربية تبدأ بالصحبة، لا بالشعار
            </h2>
            <p class="mt-5 leading-8 text-[#315e52] dark:text-[#d2e7df]/76">
                نصغي أولًا، ثم نعطي الفتى مساحة ليجرّب ويخطئ ويعيد المحاولة. المشرف هنا رفيق مسؤول، والبرنامج بيئة عملية لا محاضرة طويلة.
            </p>
            <a href="{{ route('contact') }}"
                class="mt-7 inline-flex min-h-12 items-center justify-center gap-2 whitespace-nowrap rounded-sm border border-[#007a52]/24 px-6 py-3 text-sm font-bold text-[#007a52] transition hover:bg-[#007a52] hover:text-white dark:border-[#6ee7b7]/24 dark:text-[#6ee7b7] dark:hover:bg-[#6ee7b7] dark:hover:text-[#07120f]">
                <span>تواصل معنا</span>
                <x-hugeicon name="mail-01" class="text-lg" />
            </a>
        </div>
    </section>

    <section class="bg-[#0d2b25] text-white">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-7 px-4 py-14 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8 lg:py-16">
            <div>
                <h2 class="font-heading text-3xl font-bold lg:text-4xl">اختر التجربة التي تناسب ابنك</h2>
                <p class="mt-3 max-w-2xl leading-8 text-white/72">كل فعالية توضح عمر المشاركين وموعدها وسعرها قبل أن تبدأ الحجز.</p>
            </div>
            <a href="{{ route('events.index') }}"
                class="inline-flex min-h-12 items-center justify-center gap-2 whitespace-nowrap rounded-sm bg-white px-6 py-3 text-sm font-bold text-[#0d2b25] transition hover:-translate-y-0.5 hover:bg-[#e9f7f1] active:translate-y-px motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                <span>استكشف الفعاليات</span>
                <x-hugeicon name="arrow-left-02" class="text-lg" />
            </a>
        </div>
    </section>
@endsection
