@extends('layouts.public', [
    'title' => 'قريبًا فعاليات أكثر',
    'metaDescription' => 'فعاليات وتجارب جديدة من منتجع بيرحاء قيد التجهيز، مع رحلات وورش ومخيمات سياحية قادمة قريبًا.',
])

@section('content')
    <section class="relative isolate overflow-hidden">
        <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-2/3 bg-[radial-gradient(circle_at_18%_22%,rgba(0,144,96,0.20),transparent_32%),radial-gradient(circle_at_82%_16%,rgba(24,152,176,0.16),transparent_34%),linear-gradient(135deg,rgba(223,246,239,0.96),rgba(255,255,255,0.72)_48%,rgba(248,232,196,0.48))] dark:bg-[radial-gradient(circle_at_18%_22%,rgba(0,144,96,0.18),transparent_32%),radial-gradient(circle_at_82%_16%,rgba(224,168,0,0.12),transparent_34%),linear-gradient(135deg,rgba(7,18,15,0.95),rgba(14,55,45,0.74)_54%,rgba(7,18,15,0.95))]"></div>

        <div class="mx-auto grid min-h-[calc(100dvh-5rem)] w-full max-w-7xl items-center gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(360px,0.78fr)] lg:px-8 lg:py-20">
            <div class="max-w-4xl">
                <p class="inline-flex min-h-10 items-center gap-2 rounded-sm border border-[#009060]/18 bg-white/72 px-3 py-2 text-sm font-bold text-[#009060] shadow-sm shadow-[#123329]/5 dark:border-[#e0a800]/24 dark:bg-white/8 dark:text-[#e0a800]">
                    <x-hugeicon name="calendar-03" class="text-lg" />
                    <span>رزنامة بيرحاء قيد التجهيز</span>
                </p>

                <h1 class="mt-6 max-w-4xl font-heading text-4xl font-bold leading-tight text-[#123329] sm:text-5xl lg:text-6xl dark:text-[#f7f1df]">
                    قريبًا فعاليات أكثر في بيرحاء
                </h1>

                <p class="mt-6 max-w-2xl text-lg leading-9 text-[#123329]/72 dark:text-[#f7f1df]/72">
                    نجهز موسمًا جديدًا من الرحلات والورش والمخيمات الهادئة، بتجارب سياحية تمنح العائلة وقتًا أوسع لاكتشاف المكان والناس والطبيعة.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    @if (Route::has('events.index'))
                        <a href="{{ route('events.index') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm bg-[#009060] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#009060]/18 transition hover:-translate-y-0.5 hover:bg-[#007a52] active:translate-y-0 dark:bg-[#e0a800] dark:text-[#07120f] dark:hover:bg-[#f0c63c]">
                            <span>تصفح الفعاليات الحالية</span>
                            <x-hugeicon name="arrow-left-02" class="text-lg" />
                        </a>
                    @endif

                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm border border-[#009060]/22 bg-white/64 px-6 py-3 text-sm font-bold text-[#009060] shadow-sm shadow-[#123329]/5 transition hover:-translate-y-0.5 hover:border-[#009060]/40 hover:bg-white active:translate-y-0 dark:border-[#e0a800]/26 dark:bg-white/8 dark:text-[#e0a800] dark:hover:bg-white/12">
                            <span>أنشئ حسابك</span>
                            <x-hugeicon name="user-circle" class="text-lg" />
                        </a>
                    @endif
                </div>
            </div>

            <aside class="relative overflow-hidden rounded-sm border border-[#2a8069]/14 bg-white/78 p-5 shadow-2xl shadow-[#123329]/10 dark:border-white/10 dark:bg-white/8 dark:shadow-black/30">
                <div class="absolute inset-x-5 top-5 h-24 rounded-sm bg-[linear-gradient(90deg,rgba(0,144,96,0.18),rgba(24,152,176,0.14),rgba(224,168,0,0.14))] blur-2xl"></div>

                <div class="relative grid gap-4">
                    <div class="rounded-sm border border-[#009060]/14 bg-[#f6fbf8]/82 p-5 dark:border-white/10 dark:bg-[#07120f]/72">
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-sm font-bold text-[#123329]/58 dark:text-[#f7f1df]/62">القادم في الرزنامة</span>
                            <span class="inline-flex size-11 items-center justify-center rounded-sm bg-[#009060]/10 text-[#009060] dark:bg-[#e0a800]/12 dark:text-[#e0a800]">
                                <x-hugeicon name="sparkles" class="text-2xl" />
                            </span>
                        </div>

                        <div class="mt-8 grid gap-3">
                            <div class="flex items-center gap-3 rounded-sm bg-white/76 p-3 ring-1 ring-[#2a8069]/10 dark:bg-white/8 dark:ring-white/10">
                                <x-hugeicon name="map-pin" class="text-2xl text-[#009060] dark:text-[#e0a800]" />
                                <div>
                                    <p class="font-heading text-lg font-bold text-[#123329] dark:text-[#f7f1df]">رحلات في الطبيعة</p>
                                    <p class="text-sm text-[#123329]/58 dark:text-[#f7f1df]/58">مسارات هادئة ومواقع مختارة بعناية</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 rounded-sm bg-white/76 p-3 ring-1 ring-[#2a8069]/10 dark:bg-white/8 dark:ring-white/10">
                                <x-hugeicon name="book-open-text" class="text-2xl text-[#1898b0] dark:text-[#e0a800]" />
                                <div>
                                    <p class="font-heading text-lg font-bold text-[#123329] dark:text-[#f7f1df]">ورش وتجارب تعليمية</p>
                                    <p class="text-sm text-[#123329]/58 dark:text-[#f7f1df]/58">تعلم عملي بروح سياحية مريحة</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 rounded-sm bg-white/76 p-3 ring-1 ring-[#2a8069]/10 dark:bg-white/8 dark:ring-white/10">
                                <x-hugeicon name="user-group" class="text-2xl text-[#30b070] dark:text-[#e0a800]" />
                                <div>
                                    <p class="font-heading text-lg font-bold text-[#123329] dark:text-[#f7f1df]">مخيمات عائلية</p>
                                    <p class="text-sm text-[#123329]/58 dark:text-[#f7f1df]/58">برامج مناسبة للأهل والأبناء</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="rounded-sm bg-[#009060]/10 p-4 text-center dark:bg-white/8">
                            <p class="font-heading text-3xl font-bold text-[#123329] dark:text-[#f7f1df]">٣</p>
                            <p class="mt-1 text-xs font-semibold text-[#123329]/58 dark:text-[#f7f1df]/58">مسارات</p>
                        </div>
                        <div class="rounded-sm bg-[#1898b0]/10 p-4 text-center dark:bg-white/8">
                            <p class="font-heading text-3xl font-bold text-[#123329] dark:text-[#f7f1df]">٤</p>
                            <p class="mt-1 text-xs font-semibold text-[#123329]/58 dark:text-[#f7f1df]/58">تجارب</p>
                        </div>
                        <div class="rounded-sm bg-[#e0a800]/14 p-4 text-center dark:bg-[#e0a800]/12">
                            <p class="font-heading text-3xl font-bold text-[#123329] dark:text-[#f7f1df]">قريبًا</p>
                            <p class="mt-1 text-xs font-semibold text-[#123329]/58 dark:text-[#f7f1df]/58">الحجز</p>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <section class="bg-white/58 py-14 dark:bg-white/5 lg:py-20">
        <div class="mx-auto grid w-full max-w-7xl gap-5 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
            <article class="rounded-sm border border-[#2a8069]/12 bg-white/72 p-6 shadow-sm shadow-[#123329]/5 dark:border-white/10 dark:bg-white/8 dark:shadow-black/20">
                <x-hugeicon name="clock-01" class="text-3xl text-[#009060] dark:text-[#e0a800]" />
                <h2 class="mt-5 font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">إطلاق متدرج</h2>
                <p class="mt-3 text-sm leading-7 text-[#123329]/66 dark:text-[#f7f1df]/66">سنضيف الفعاليات تباعًا حتى تكون التفاصيل والأسعار والمقاعد واضحة قبل فتح الحجز.</p>
            </article>

            <article class="rounded-sm border border-[#2a8069]/12 bg-white/72 p-6 shadow-sm shadow-[#123329]/5 dark:border-white/10 dark:bg-white/8 dark:shadow-black/20">
                <x-hugeicon name="check-list" class="text-3xl text-[#1898b0] dark:text-[#e0a800]" />
                <h2 class="mt-5 font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">تفاصيل مكتملة</h2>
                <p class="mt-3 text-sm leading-7 text-[#123329]/66 dark:text-[#f7f1df]/66">كل تجربة ستصل بصفحة واضحة للبرنامج، الفئة المناسبة، خيارات الدفع، وما يحتاجه المشارك قبل الوصول.</p>
            </article>

            <article class="rounded-sm border border-[#2a8069]/12 bg-white/72 p-6 shadow-sm shadow-[#123329]/5 dark:border-white/10 dark:bg-white/8 dark:shadow-black/20">
                <x-hugeicon name="mail-01" class="text-3xl text-[#30b070] dark:text-[#e0a800]" />
                <h2 class="mt-5 font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">تابع التحديثات</h2>
                <p class="mt-3 text-sm leading-7 text-[#123329]/66 dark:text-[#f7f1df]/66">احتفظ بحسابك جاهزًا، وتابع صفحة الفعاليات عند إعلان الدفعة القادمة من برامج بيرحاء.</p>
            </article>
        </div>
    </section>
@endsection
