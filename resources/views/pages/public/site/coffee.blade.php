@extends('layouts.public', [
    'title' => $title,
    'metaDescription' => $metaDescription,
    'metaImage' => $metaImage,
])

@php
    $whatsappUrl = 'https://wa.me/'.config('coffee.whatsapp_number').'?text='.rawurlencode(config('coffee.whatsapp_message'));
    $walletsEnabled = config('byruhaa.wallets.enabled', false) && config('byruhaa.minor_accounts.enabled', true);

    if (auth('minor-profile')->check()) {
        $walletActionUrl = route('minor.dashboard').'#wallet';
        $walletActionLabel = 'اذهب إلى حسابي ومحفظتي';
    } elseif (auth('customer')->check()) {
        $walletActionUrl = route('customer.minor-profiles.index');
        $walletActionLabel = 'إدارة حسابات الأبناء';
    } else {
        $walletActionUrl = route('register');
        $walletActionLabel = 'أنشئ حساب وليّ الأمر';
    }
@endphp

@section('content')
    <div>
        <section data-coffee-hero class="relative isolate min-h-[calc(100dvh-5rem)] overflow-hidden bg-[#07120f] text-[#f7f1df]" aria-labelledby="coffee-hero-title">
            <picture class="absolute inset-0 -z-20 block size-full">
                <source media="(max-width: 767px)" srcset="{{ asset('images/coffee-byruha-hero-mobile.webp') }}" width="941" height="1672">
                <img data-coffee-hero-media src="{{ asset('images/coffee-byruha-hero.webp') }}" alt="تحضير القهوة في فناء بيرحاء التراثي بإبراء" width="1774" height="887" fetchpriority="high" class="size-full object-cover object-center">
            </picture>
            <div class="pointer-events-none absolute inset-0 -z-10 bg-[linear-gradient(180deg,rgba(7,18,15,0.90)_0%,rgba(7,18,15,0.74)_36%,rgba(7,18,15,0.30)_68%,rgba(7,18,15,0.62)_100%)] md:bg-[linear-gradient(90deg,rgba(7,18,15,0.94)_0%,rgba(7,18,15,0.78)_34%,rgba(7,18,15,0.30)_66%,rgba(7,18,15,0.18)_100%)]"></div>
            <div class="pointer-events-none absolute inset-0 -z-10 bg-[linear-gradient(0deg,rgba(7,18,15,0.42),transparent_34%)]"></div>

            <div class="mx-auto flex min-h-[calc(100dvh-5rem)] w-full max-w-7xl items-start px-4 pb-20 pt-12 sm:px-6 sm:pt-16 md:items-center md:justify-end md:py-16 lg:px-8">
                <div class="public-hero-copy w-full max-w-xl drop-shadow-[0_3px_18px_rgba(0,0,0,0.38)] md:w-[48%]">
                    <p class="inline-flex items-center gap-2 text-sm font-bold text-[#a7f3d0]">
                        <span class="h-px w-8 bg-[#a7f3d0]/72" aria-hidden="true"></span>
                        قهوة بيرحاء في إبراء
                    </p>
                    <h1 id="coffee-hero-title" class="mt-5 max-w-[11ch] font-heading text-5xl font-black leading-[1.12] text-[#f7f1df] sm:text-6xl lg:text-7xl">
                        ليس مقهى، بل عالم
                    </h1>
                    <p class="mt-6 max-w-[42ch] text-base leading-8 text-[#edf8f3]/88 sm:text-lg">
                        قهوة وحلوى في مكان يجمع العبادة والعلم والعمل واللعب والنمو لطلاب الصف السابع إلى الثاني عشر.
                    </p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                        <a href="#menu" class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-sm bg-[#007a52] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-black/25 transition hover:-translate-y-0.5 hover:bg-[#009060] active:translate-y-px focus:outline-none focus-visible:ring-2 focus-visible:ring-[#a7f3d0] motion-reduce:transform-none motion-reduce:transition-none sm:w-auto">
                            تصفّح القائمة
                            <x-hugeicon name="arrow-left-02" class="text-lg" />
                        </a>
                        <a href="{{ $walletsEnabled ? '#family-wallets' : '#place' }}" class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-sm border border-white/28 bg-[#07120f]/34 px-6 py-3 text-sm font-bold text-[#f7f1df] shadow-[inset_0_1px_0_rgba(255,255,255,0.12)] backdrop-blur-sm transition hover:-translate-y-0.5 hover:border-[#a7f3d0]/70 hover:bg-[#07120f]/50 active:translate-y-px focus:outline-none focus-visible:ring-2 focus-visible:ring-[#a7f3d0] motion-reduce:transform-none motion-reduce:transition-none sm:w-auto">
                            {{ $walletsEnabled ? 'محافظ الأبناء' : 'تعرّف على المكان' }}
                            <x-hugeicon :name="$walletsEnabled ? 'wallet-02' : 'map-pin'" class="text-lg" />
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-y border-[#2a8069]/12 bg-white/70 dark:border-white/10 dark:bg-white/[0.03]" aria-label="معلومات الطلب">
            <dl class="mx-auto grid w-full max-w-7xl divide-y divide-[#2a8069]/12 px-4 sm:grid-cols-3 sm:divide-x sm:divide-x-reverse sm:divide-y-0 sm:px-6 lg:px-8 dark:divide-white/10">
                <div class="flex items-center gap-4 py-5 sm:px-6 sm:first:pe-0">
                    <x-hugeicon name="home-01" class="shrink-0 text-2xl text-[#007a52] dark:text-[#6ee7b7]" />
                    <div><dt class="text-xs text-[#315e52]/68 dark:text-[#d2e7df]/62">طريقة الاستلام</dt><dd class="mt-1 font-bold text-[#123329] dark:text-[#f7f1df]">من قهوة بيرحاء</dd></div>
                </div>
                <div class="flex items-center gap-4 py-5 sm:px-6">
                    <x-hugeicon name="payment-02" class="shrink-0 text-2xl text-[#007a52] dark:text-[#6ee7b7]" />
                    <div><dt class="text-xs text-[#315e52]/68 dark:text-[#d2e7df]/62">الدفع الإلكتروني</dt><dd class="mt-1 font-bold text-[#123329] dark:text-[#f7f1df]">آمن عبر ثواني</dd></div>
                </div>
                <div class="flex items-center gap-4 py-5 sm:px-6 sm:last:ps-0">
                    <x-hugeicon :name="$walletsEnabled ? 'wallet-02' : 'user-circle'" class="shrink-0 text-2xl text-[#007a52] dark:text-[#6ee7b7]" />
                    <div><dt class="text-xs text-[#315e52]/68 dark:text-[#d2e7df]/62">{{ $walletsEnabled ? 'حسابات الأبناء' : 'حساب العميل' }}</dt><dd class="mt-1 font-bold text-[#123329] dark:text-[#f7f1df]">{{ $walletsEnabled ? 'ضمن صلاحيات وليّ الأمر' : 'لمتابعة الطلبات والفواتير' }}</dd></div>
                </div>
            </dl>
        </section>

        <livewire:store.coffee-store />

        <section id="place" class="scroll-mt-24 border-t border-[#2a8069]/12 bg-white/62 dark:border-white/10 dark:bg-white/[0.03]" aria-labelledby="place-title">
            <div class="mx-auto grid w-full max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[0.92fr_1.08fr] lg:items-center lg:px-8 lg:py-24">
                <div class="public-card overflow-hidden rounded-sm border border-[#2a8069]/14 bg-white dark:border-white/10 dark:bg-white/5">
                    <img src="{{ asset('images/coffee-byruha-menu.webp') }}" alt="قهوة ومخبوزات من قائمة بيرحاء" width="1536" height="1024" loading="lazy" class="aspect-[4/3] h-full w-full object-cover">
                </div>

                <div class="max-w-2xl">
                    <h2 id="place-title" class="font-heading text-3xl font-bold leading-tight text-[#123329] sm:text-4xl lg:text-5xl dark:text-[#f7f1df]">مكان يجمع يوم الفتى</h2>
                    <p class="mt-5 max-w-[58ch] leading-8 text-[#315e52] dark:text-[#d2e7df]/78">المقهى داخل مخيم بيرحاء، لذلك تمتد التجربة من الفنجان إلى خمسة معانٍ يعيشها الزائر في المكان.</p>

                    <div class="mt-8 grid gap-x-8 sm:grid-cols-2">
                        @foreach ([
                            ['عبادة', 'مسجد قريب يربط اليوم بالصلاة والسكينة.'],
                            ['علم', 'مكتبة ومساحة تساعدان على القراءة والتعلّم.'],
                            ['عمل', 'بيئة تشجّع المبادرة وتحمل المسؤولية.'],
                            ['لعب', 'ملعب وتجارب ترفيهية للحركة والصحبة.'],
                            ['نموّ', 'اختيارات يومية تبني الاستقلال خطوة بخطوة.'],
                        ] as [$value, $description])
                            <div class="border-t border-[#2a8069]/16 py-4 dark:border-white/12">
                                <h3 class="font-heading text-xl font-bold text-[#123329] dark:text-[#f7f1df]">{{ $value }}</h3>
                                <p class="mt-1 text-sm leading-6 text-[#315e52] dark:text-[#d2e7df]/72">{{ $description }}</p>
                            </div>
                        @endforeach
                    </div>

                    <ul class="mt-7 flex flex-wrap gap-2" aria-label="مرافق مخيم بيرحاء">
                        @foreach (['مسجد', 'مكتبة', 'ملعب متعدد الأغراض', 'سينما سباعية الأبعاد', 'حديقة'] as $facility)
                            <li class="rounded-sm border border-[#2a8069]/16 bg-[#f6fbf8] px-3 py-2 text-xs font-bold text-[#315e52] dark:border-white/12 dark:bg-white/5 dark:text-[#d2e7df]">{{ $facility }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </section>

        @if ($walletsEnabled)
            <section id="family-wallets" class="scroll-mt-24 border-t border-[#2a8069]/12 bg-[#0c2a20] text-white dark:border-white/10 dark:bg-[#0a211a]" aria-labelledby="wallets-title">
                <div class="mx-auto grid w-full max-w-7xl gap-12 px-4 py-16 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:items-start lg:px-8 lg:py-24">
                    <div class="public-card lg:sticky lg:top-28">
                        <p class="text-sm font-bold text-[#f0c96a]">لوليّ الأمر</p>
                        <h2 id="wallets-title" class="mt-3 max-w-[13ch] font-heading text-3xl font-bold leading-tight text-white sm:text-4xl lg:text-5xl">حساب واحد، ومحفظة لكل ابن</h2>
                        <p class="mt-5 max-w-[48ch] leading-8 text-[#d2e7df]/82">اشحن الرصيد عبر ثواني، وحدد صلاحية الدفع، وتابع الحركات من حسابك.</p>
                        <a href="{{ $walletActionUrl }}" class="mt-7 inline-flex min-h-12 items-center justify-center gap-2 rounded-sm bg-[#e0a800] px-6 py-3 text-sm font-bold text-[#123329] transition hover:-translate-y-0.5 hover:bg-[#f0c96a] active:translate-y-px focus:outline-none focus-visible:ring-2 focus-visible:ring-white motion-reduce:transform-none motion-reduce:transition-none">
                            {{ $walletActionLabel }}
                            <x-hugeicon name="arrow-left-02" class="text-lg" />
                        </a>
                        <x-policy-links :pages="['student-accounts', 'wallet', 'refund-cancellation']" label="سياسات حسابات الأبناء والمحفظة" class="mt-5 !text-[#d2e7df]/72 [&_a]:!text-[#a7f3d0] [&_a:hover]:!text-white" />
                    </div>

                    <ol class="grid gap-0" aria-label="طريقة استخدام محافظ الأبناء">
                        @foreach ([
                            ['user-circle', 'أنشئ حساب وليّ الأمر', 'استخدم رقم هاتفك للدخول إلى حسابك ومتابعة الأسرة.'],
                            ['user-group', 'أضف ابنك وأعدّ دخوله', 'اربطه بعضو الأسرة وفعّل بيانات الدخول الخاصة به.'],
                            ['payment-02', 'اشحن المحفظة عبر ثواني', 'اختر المبلغ ثم أكمل الدفع الإلكتروني الآمن.'],
                            ['wallet-02', 'اسمح بالدفع وتابع الحركات', 'تحكم في صلاحية الإنفاق وراجع الرصيد والعمليات من حسابك.'],
                        ] as [$icon, $heading, $description])
                            <li class="grid grid-cols-[auto_1fr] gap-4 border-t border-white/14 py-6 first:border-t-0 first:pt-0 last:pb-0">
                                <span class="flex size-11 items-center justify-center rounded-sm bg-white/10 text-[#f0c96a]" aria-hidden="true"><x-hugeicon :name="$icon" class="text-xl" /></span>
                                <div><h3 class="font-heading text-xl font-bold text-white">{{ $heading }}</h3><p class="mt-2 max-w-[52ch] text-sm leading-7 text-[#d2e7df]/76">{{ $description }}</p></div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>
        @endif

        <section class="border-t border-[#2a8069]/12 bg-[#e9f7f0]/66 dark:border-white/10 dark:bg-white/[0.03]" aria-labelledby="visit-title">
            <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-4 py-14 sm:px-6 lg:flex-row lg:items-end lg:justify-between lg:px-8 lg:py-16">
                <div class="max-w-3xl">
                    <h2 id="visit-title" class="font-heading text-3xl font-bold text-[#123329] sm:text-4xl dark:text-[#f7f1df]">زرنا في بيرحاء</h2>
                    <p class="mt-4 max-w-[58ch] leading-8 text-[#315e52] dark:text-[#d2e7df]/76">{{ $coffee['location'] }}. للاستفسار عن الوصول أو توفر المنتجات، تواصل مع مساعدنا عبر واتساب.</p>
                </div>
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm bg-[#007a52] px-6 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-[#006746] active:translate-y-px focus:outline-none focus-visible:ring-2 focus-visible:ring-[#e0a800] motion-reduce:transform-none motion-reduce:transition-none">
                    تواصل معنا
                    <x-hugeicon name="mail-01" class="text-lg" />
                </a>
            </div>
        </section>
    </div>
@endsection
