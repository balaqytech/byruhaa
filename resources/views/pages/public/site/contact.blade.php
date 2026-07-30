@extends('layouts.public', [
    'title' => $title,
    'metaDescription' => $metaDescription,
])

@section('content')
    <section class="relative isolate overflow-hidden">
        <div
            class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_82%_18%,rgba(0,144,96,0.16),transparent_34%),radial-gradient(circle_at_14%_80%,rgba(24,152,176,0.11),transparent_30%)] dark:bg-[radial-gradient(circle_at_82%_18%,rgba(52,211,153,0.10),transparent_34%),radial-gradient(circle_at_14%_80%,rgba(24,152,176,0.08),transparent_30%)]">
        </div>

        <div
            class="mx-auto grid min-h-[calc(100dvh-5rem)] w-full max-w-7xl items-center gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,0.82fr)_minmax(440px,1.18fr)] lg:px-8 lg:py-16">
            <div class="public-hero-copy max-w-2xl">
                <p
                    class="inline-flex min-h-10 items-center gap-2 rounded-sm border border-[#009060]/20 bg-white/72 px-3 py-2 text-sm font-bold text-[#007a52] shadow-sm shadow-[#123329]/5 dark:border-[#34d399]/20 dark:bg-white/6 dark:text-[#6ee7b7]">
                    <x-hugeicon name="mail-01" class="text-lg" />
                    {{ $contact->eyebrow }}
                </p>

                <h1
                    class="mt-5 max-w-2xl font-heading text-4xl font-bold leading-[1.2] text-[#123329] sm:text-5xl lg:text-6xl dark:text-[#f7f1df]">
                    {{ $contact->page_title }}
                </h1>

                <p class="mt-6 max-w-[55ch] text-lg leading-8 text-[#315e52] dark:text-[#d2e7df]/80">
                    {{ $contact->intro }}
                </p>

                <a href="{{ $assistantUrl }}" target="_blank" rel="noopener noreferrer"
                    class="mt-8 inline-flex min-h-12 items-center justify-center gap-2 whitespace-nowrap rounded-sm bg-[#25b75d] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#25b75d]/18 transition hover:-translate-y-0.5 hover:bg-[#209e51] active:translate-y-px motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                    <span>{{ $contact->assistant_button_label }}</span>
                    <x-hugeicon name="arrow-left-02" class="text-lg" />
                </a>
            </div>

            <aside
                class="relative overflow-hidden rounded-sm border border-[#2a8069]/14 bg-[#123329] p-7 text-white shadow-[0_32px_90px_rgba(18,51,41,0.16)] sm:p-10 dark:border-white/10 dark:bg-[#0c1e19] dark:shadow-black/30">
                <div class="absolute -start-20 -top-20 size-64 rounded-full bg-[#009060]/28 blur-3xl"></div>
                <div class="relative">
                    <span class="flex size-14 items-center justify-center rounded-sm bg-[#25d366] text-white">
                        <x-hugeicon name="mail-01" class="text-3xl" />
                    </span>
                    <p class="mt-10 text-sm font-bold text-[#87d9bd]">المسار الأسرع</p>
                    <h2 class="mt-3 font-heading text-3xl font-bold leading-tight sm:text-4xl">{{ $contact->assistant_title }}</h2>
                    <p class="mt-5 max-w-[52ch] leading-8 text-white/70">{{ $contact->assistant_description }}</p>
                    <div class="mt-8 flex items-center gap-3 border-t border-white/16 pt-6 text-sm text-white/62">
                        <x-hugeicon name="clock-01" class="text-xl text-[#f5d478]" />
                        <span>متاح طوال اليوم عبر واتساب</span>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <section class="border-y border-[#2a8069]/12 bg-white/62 dark:border-white/10 dark:bg-white/[0.03]">
        <div class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
            <div class="grid gap-10 lg:grid-cols-[minmax(0,0.7fr)_minmax(0,1.3fr)] lg:gap-20">
                <div>
                    <p class="text-sm font-bold text-[#007a52] dark:text-[#6ee7b7]">بيانات واضحة</p>
                    <h2 class="mt-3 font-heading text-3xl font-bold leading-tight text-[#123329] lg:text-5xl dark:text-[#f7f1df]">اتصل أو خطط لزيارتك</h2>
                </div>

                <dl class="grid border-b border-[#2a8069]/14 sm:grid-cols-2 dark:border-white/10">
                    <div class="border-t border-[#2a8069]/14 py-7 sm:pe-8 dark:border-white/10">
                        <dt class="flex items-center gap-2 text-sm font-bold text-[#007a52] dark:text-[#6ee7b7]">
                            <x-hugeicon name="user-circle" class="text-lg" />
                            الهاتف
                        </dt>
                        <dd class="mt-3 text-lg font-semibold text-[#123329] dark:text-[#f7f1df]">
                            <a href="tel:{{ $phoneHref }}" class="transition hover:text-[#007a52] dark:hover:text-[#6ee7b7]">{{ $contact->phone }}</a>
                        </dd>
                    </div>
                    <div class="border-t border-[#2a8069]/14 py-7 sm:border-s sm:ps-8 dark:border-white/10">
                        <dt class="flex items-center gap-2 text-sm font-bold text-[#007a52] dark:text-[#6ee7b7]">
                            <x-hugeicon name="mail-01" class="text-lg" />
                            البريد الإلكتروني
                        </dt>
                        <dd class="mt-3 break-words text-lg font-semibold text-[#123329] dark:text-[#f7f1df]">
                            @if ($contact->email)
                                <a href="mailto:{{ $contact->email }}" class="transition hover:text-[#007a52] dark:hover:text-[#6ee7b7]">{{ $contact->email }}</a>
                            @else
                                <span class="text-[#315e52]/58 dark:text-[#d2e7df]/52">يُضاف من لوحة الإدارة</span>
                            @endif
                        </dd>
                    </div>
                    <div class="border-t border-[#2a8069]/14 py-7 sm:pe-8 dark:border-white/10">
                        <dt class="flex items-center gap-2 text-sm font-bold text-[#007a52] dark:text-[#6ee7b7]">
                            <x-hugeicon name="map-pin" class="text-lg" />
                            الموقع
                        </dt>
                        <dd class="mt-3 leading-7 text-[#123329] dark:text-[#f7f1df]">{{ $contact->location }}</dd>
                        @if ($mapUrl)
                            <a href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer"
                                class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-[#007a52] hover:underline dark:text-[#6ee7b7]">
                                افتح الخريطة
                                <x-hugeicon name="arrow-left-02" class="text-base" />
                            </a>
                        @endif
                    </div>
                    <div class="border-t border-[#2a8069]/14 py-7 sm:border-s sm:ps-8 dark:border-white/10">
                        <dt class="flex items-center gap-2 text-sm font-bold text-[#007a52] dark:text-[#6ee7b7]">
                            <x-hugeicon name="calendar-03" class="text-lg" />
                            الزيارة
                        </dt>
                        <dd class="mt-3 leading-7 text-[#123329] dark:text-[#f7f1df]">{{ $contact->visiting_hours }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </section>

    <section>
        <div
            class="mx-auto grid w-full max-w-7xl gap-10 px-4 py-18 sm:px-6 lg:grid-cols-[minmax(0,0.82fr)_minmax(0,1.18fr)] lg:items-start lg:px-8 lg:py-24">
            <div class="max-w-xl">
                <p class="text-sm font-bold text-[#007a52] dark:text-[#6ee7b7]">الحسابات الرسمية</p>
                <h2 class="mt-3 font-heading text-3xl font-bold text-[#123329] lg:text-5xl dark:text-[#f7f1df]">{{ $contact->social_heading }}</h2>
                <p class="mt-5 leading-8 text-[#315e52] dark:text-[#d2e7df]/76">{{ $contact->social_intro }}</p>
            </div>

            @if ($socialLinks !== [])
                <nav class="grid border-b border-[#2a8069]/14 sm:grid-cols-2 dark:border-white/10" aria-label="حسابات بيرحاء الاجتماعية">
                    @foreach ($socialLinks as $socialLink)
                        <a href="{{ $socialLink['url'] }}" target="_blank" rel="noopener noreferrer"
                            class="group flex items-center justify-between gap-4 border-t border-[#2a8069]/14 py-6 text-lg font-bold text-[#123329] transition hover:text-[#007a52] sm:odd:pe-7 sm:even:border-s sm:even:ps-7 dark:border-white/10 dark:text-[#f7f1df] dark:hover:text-[#6ee7b7]">
                            <span>{{ $socialLink['label'] }}</span>
                            <x-hugeicon name="arrow-left-02" class="text-xl transition group-hover:-translate-x-1 motion-reduce:transition-none" />
                        </a>
                    @endforeach
                </nav>
            @else
                <div class="rounded-sm border border-dashed border-[#2a8069]/24 bg-white/48 p-8 dark:border-white/16 dark:bg-white/[0.03]">
                    <x-hugeicon name="sparkles" class="text-3xl text-[#007a52] dark:text-[#6ee7b7]" />
                    <p class="mt-4 font-semibold leading-8 text-[#315e52] dark:text-[#d2e7df]/72">ستظهر حسابات بيرحاء الرسمية هنا فور إضافتها من لوحة الإدارة.</p>
                </div>
            @endif
        </div>
    </section>

    <section class="border-t border-[#2a8069]/12 bg-white/62 dark:border-white/10 dark:bg-white/[0.03]">
        <div
            class="mx-auto grid w-full max-w-7xl gap-8 px-4 py-14 sm:px-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end lg:px-8 lg:py-16">
            <div class="max-w-3xl">
                <h2 class="font-heading text-3xl font-bold text-[#123329] lg:text-4xl dark:text-[#f7f1df]">لست متأكدًا من أين تبدأ؟</h2>
                <p class="mt-4 leading-8 text-[#315e52] dark:text-[#d2e7df]/76">صف للمساعد ما تبحث عنه، وسيقترح عليك البرنامج أو وسيلة التواصل المناسبة.</p>
            </div>
            <a href="{{ $assistantUrl }}" target="_blank" rel="noopener noreferrer"
                class="inline-flex min-h-12 items-center justify-center gap-2 whitespace-nowrap rounded-sm bg-[#25b75d] px-6 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-[#209e51] active:translate-y-px motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                <span>{{ $contact->assistant_button_label }}</span>
                <x-hugeicon name="arrow-left-02" class="text-lg" />
            </a>
        </div>
    </section>
@endsection
