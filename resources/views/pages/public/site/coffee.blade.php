@extends('layouts.public', [
    'title' => $title,
    'metaDescription' => $metaDescription,
    'metaImage' => $metaImage,
])

@php
    $whatsappUrl = 'https://wa.me/'.config('coffee.whatsapp_number').'?text='.rawurlencode(config('coffee.whatsapp_message'));
@endphp

@section('content')
    <main>
        <section class="relative isolate overflow-hidden bg-[#f6fbf8] dark:bg-[#07120f]">
            <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_80%_10%,rgba(0,144,96,0.16),transparent_34%),radial-gradient(circle_at_12%_90%,rgba(224,168,0,0.10),transparent_30%)]"></div>
            <div class="mx-auto grid min-h-[min(760px,calc(100dvh-5rem))] w-full max-w-7xl items-center gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[0.84fr_1.16fr] lg:px-8 lg:py-20">
                <div class="public-hero-copy max-w-2xl">
                    <p class="inline-flex items-center gap-2 rounded-sm border border-[#007a52]/20 bg-white/80 px-3 py-2 text-sm font-bold text-[#007a52] shadow-sm dark:border-[#6ee7b7]/20 dark:bg-white/5 dark:text-[#6ee7b7]">
                        <x-hugeicon name="sparkles" class="text-lg" />
                        قهوة بيرحاء
                    </p>
                    <h1 class="mt-6 font-heading text-4xl font-bold leading-[1.18] text-[#123329] sm:text-5xl lg:text-6xl dark:text-[#f7f1df]">
                        الأول في سلطنة عُمان<br>لصناعة يومٍ أهدأ
                    </h1>
                    <p class="mt-6 max-w-[55ch] text-lg leading-8 text-[#315e52] dark:text-[#d2e7df]/80">
                        قهوة بيرحاء مساحة يومية منظمة لطلاب الصف السابع إلى الثاني عشر، وللمربين الذين يرافقونهم. اختر مشروبك، ادفع بأمان، واستلمه من المكان.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="#menu" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm bg-[#007a52] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#007a52]/20 transition hover:-translate-y-0.5 hover:bg-[#006746] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#e0a800] motion-reduce:transition-none">
                            تصفّح القائمة
                            <x-hugeicon name="arrow-left-02" class="text-lg" />
                        </a>
                        <a href="{{ route('register') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm border border-[#123329]/18 bg-white/75 px-6 py-3 text-sm font-bold text-[#123329] transition hover:-translate-y-0.5 hover:border-[#007a52]/40 hover:text-[#007a52] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#e0a800] dark:border-white/16 dark:bg-white/5 dark:text-[#f7f1df] dark:hover:text-[#6ee7b7] motion-reduce:transition-none">
                            أنشئ حسابًا لمتابعة طلباتك
                            <x-hugeicon name="user-add-01" class="text-lg" />
                        </a>
                    </div>
                    <dl class="mt-10 grid gap-5 border-t border-[#2a8069]/16 pt-6 sm:grid-cols-3 dark:border-white/12">
                        <div><dt class="text-sm text-[#315e52]/70 dark:text-[#d2e7df]/58">المكان</dt><dd class="mt-1 font-bold text-[#123329] dark:text-[#f7f1df]">إبراء</dd></div>
                        <div><dt class="text-sm text-[#315e52]/70 dark:text-[#d2e7df]/58">الاستلام</dt><dd class="mt-1 font-bold text-[#123329] dark:text-[#f7f1df]">من الموقع</dd></div>
                        <div><dt class="text-sm text-[#315e52]/70 dark:text-[#d2e7df]/58">الدفع</dt><dd class="mt-1 font-bold text-[#123329] dark:text-[#f7f1df]">إلكتروني وآمن</dd></div>
                    </dl>
                </div>
                <div class="relative mx-auto w-full max-w-3xl">
                    <div class="overflow-hidden rounded-sm border border-[#2a8069]/14 bg-white shadow-[0_32px_90px_rgba(18,51,41,0.16)] dark:border-white/10 dark:bg-white/5 dark:shadow-black/30">
                        <img src="{{ asset('images/coffee-byruha-hero.webp') }}" alt="تحضير قهوة بيرحاء في إبراء" width="1536" height="1024" fetchpriority="high" class="aspect-[3/2] h-full w-full object-cover">
                    </div>
                    <p class="relative z-10 mt-4 max-w-md rounded-sm border border-[#2a8069]/14 bg-white px-5 py-4 text-sm font-semibold leading-7 text-[#123329] shadow-xl shadow-[#123329]/10 dark:border-white/10 dark:bg-[#0c1e19] dark:text-[#f7f1df] sm:ms-auto">
                        قائمة واضحة، سعر نهائي، واستلام من المكان في الوقت الذي يناسبك.
                    </p>
                </div>
            </div>
        </section>

        <section class="border-y border-[#2a8069]/12 bg-white/62 dark:border-white/10 dark:bg-white/[0.03]">
            <div class="mx-auto grid w-full max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[1.12fr_0.88fr] lg:items-center lg:px-8 lg:py-20">
                <div class="overflow-hidden rounded-sm border border-[#2a8069]/14 bg-white dark:border-white/10 dark:bg-white/5">
                    <img src="{{ asset('images/coffee-byruha-menu.webp') }}" alt="مشروبات ومخبوزات قهوة بيرحاء" width="1536" height="1024" loading="lazy" class="aspect-[3/2] h-full w-full object-cover">
                </div>
                <div class="max-w-2xl">
                    <p class="text-sm font-bold tracking-[0.18em] text-[#b07c00]">ضيافة بيرحاء</p>
                    <h2 class="mt-3 font-heading text-3xl font-bold leading-tight text-[#123329] lg:text-5xl dark:text-[#f7f1df]">القهوة امتدادٌ للمكان</h2>
                    <p class="mt-5 leading-8 text-[#315e52] dark:text-[#d2e7df]/76">ليست قائمة ضخمة ولا متجرًا منفصلًا؛ هي محطة يومية للزائر، ومساحة لقاء خفيفة قبل البرنامج أو بعده، بخيارات معروفة وسعر نهائي ظاهر من البداية.</p>
                    <div class="mt-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                        <div class="border-t border-[#2a8069]/18 pt-4 dark:border-white/12"><p class="font-heading text-xl font-bold text-[#123329] dark:text-[#f7f1df]">معرفة</p><p class="mt-2 leading-7 text-[#315e52] dark:text-[#d2e7df]/72">نتعلم من التفاصيل.</p></div>
                        <div class="border-t border-[#2a8069]/18 pt-4 dark:border-white/12"><p class="font-heading text-xl font-bold text-[#123329] dark:text-[#f7f1df]">صحبة</p><p class="mt-2 leading-7 text-[#315e52] dark:text-[#d2e7df]/72">نلتقي باحترام.</p></div>
                        <div class="border-t border-[#2a8069]/18 pt-4 dark:border-white/12"><p class="font-heading text-xl font-bold text-[#123329] dark:text-[#f7f1df]">مسؤولية</p><p class="mt-2 leading-7 text-[#315e52] dark:text-[#d2e7df]/72">نختار ونلتزم.</p></div>
                        <div class="border-t border-[#2a8069]/18 pt-4 dark:border-white/12"><p class="font-heading text-xl font-bold text-[#123329] dark:text-[#f7f1df]">أصالة</p><p class="mt-2 leading-7 text-[#315e52] dark:text-[#d2e7df]/72">نحفظ معنى المكان.</p></div>
                        <div class="border-t border-[#2a8069]/18 pt-4 dark:border-white/12"><p class="font-heading text-xl font-bold text-[#123329] dark:text-[#f7f1df]">أثر</p><p class="mt-2 leading-7 text-[#315e52] dark:text-[#d2e7df]/72">يستمر بعد الزيارة.</p></div>
                    </div>
                </div>
            </div>
        </section>

        <livewire:store.coffee-store />

        <section class="border-t border-[#2a8069]/12 bg-[#e9f7f0]/65 dark:border-white/10 dark:bg-[#0c2a20]/35">
            <div class="mx-auto grid w-full max-w-7xl gap-8 px-4 py-14 sm:px-6 lg:grid-cols-[0.8fr_1.2fr] lg:items-center lg:px-8 lg:py-16">
                <div>
                    <p class="text-sm font-bold tracking-[0.18em] text-[#b07c00]">لولي الأمر</p>
                    <h2 class="mt-3 font-heading text-3xl font-bold text-[#123329] lg:text-4xl dark:text-[#f7f1df]">طلب واضح، وبيئة تعرفها</h2>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-sm border border-[#2a8069]/14 bg-white/80 p-4 dark:border-white/10 dark:bg-white/5"><p class="font-bold text-[#123329] dark:text-[#f7f1df]">سعر نهائي</p><p class="mt-2 text-sm leading-6 text-[#315e52] dark:text-[#d2e7df]/70">الأسعار تشمل ٥٪ ضريبة القيمة المضافة.</p></div>
                    <div class="rounded-sm border border-[#2a8069]/14 bg-white/80 p-4 dark:border-white/10 dark:bg-white/5"><p class="font-bold text-[#123329] dark:text-[#f7f1df]">حساب واحد</p><p class="mt-2 text-sm leading-6 text-[#315e52] dark:text-[#d2e7df]/70">أنشئ حسابًا لمتابعة الطلبات والفواتير.</p></div>
                    <div class="rounded-sm border border-[#2a8069]/14 bg-white/80 p-4 dark:border-white/10 dark:bg-white/5"><p class="font-bold text-[#123329] dark:text-[#f7f1df]">تواصل مباشر</p><p class="mt-2 text-sm leading-6 text-[#315e52] dark:text-[#d2e7df]/70">نحن متاحون عبر واتساب عند الحاجة.</p></div>
                </div>
            </div>
        </section>

        <section class="border-t border-[#2a8069]/12 bg-white/62 dark:border-white/10 dark:bg-white/[0.03]">
            <div class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-4 py-14 sm:px-6 lg:flex-row lg:items-end lg:justify-between lg:px-8 lg:py-16">
                <div class="max-w-3xl"><h2 class="font-heading text-3xl font-bold text-[#123329] lg:text-4xl dark:text-[#f7f1df]">زرنا في بيرحاء</h2><p class="mt-4 leading-8 text-[#315e52] dark:text-[#d2e7df]/76">إبراء، سلطنة عُمان. للاستفسار عن التوفر أو الوصول، تواصل مع مساعدنا الذكي عبر واتساب.</p></div>
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm bg-[#007a52] px-6 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-[#006746] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#e0a800] motion-reduce:transition-none">تواصل معنا <x-hugeicon name="mail-01" class="text-lg" /></a>
            </div>
        </section>
    </main>
@endsection
