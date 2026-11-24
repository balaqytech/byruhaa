@extends('layouts.public', [
    'title' => 'عرض العيد الوطني ٥٦',
    'metaDescription' => 'ست هدايا من قهوة بيرحاء بمناسبة العيد الوطني ٥٦. تعرّف على تفاصيل العرض.',
    'robots' => 'noindex',
])

@section('content')
<div class="bg-[#f6f1e6] text-[#173729]">
    @php
        $assistantUrl = 'https://wa.me/'.config('coffee.whatsapp_number').'?text='.rawurlencode('أرغب بالتحدث مع المساعد الذكي عن عرض العيد الوطني ٥٦.');
        $ctaClass = 'inline-flex min-h-12 items-center justify-center rounded-xl bg-[#e2c47a] px-6 py-3 font-bold text-[#0b3527] hover:bg-[#f2d88b] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#e2c47a]';
        $gifts = [
            ['title' => 'دورة: كيف أحفظ القرآن؟', 'description' => 'يحفظ بخطةٍ واضحة… ويثبُت ما يحفظ.', 'value' => '٣٠ ر.ع', 'image' => 'f-quran.jpg'],
            ['title' => 'كتاب تلقين الصبيان — طبعة سدرة', 'description' => 'دليله إلى فقه العبادات بقلم الإمام السالمي.', 'value' => '٨ ر.ع', 'image' => 'f-book.jpg'],
            ['title' => 'دورة: أسرار التفوق الدراسي', 'description' => 'يذاكر بذكاء… ويتفوّق بثقة.', 'value' => '٣٠ ر.ع', 'image' => null],
            ['title' => 'تذاكر سينما بيرحاء', 'description' => 'مغامرةٌ سباعية الأبعاد يعيشها بكل حواسه.', 'value' => '٢ ر.ع', 'image' => 'f-cinema.jpg'],
            ['title' => 'ملعب بيرحاء', 'description' => 'يلعب ويتنافس مع رفاقه في بيئةٍ آمنة.', 'value' => '٣ ر.ع', 'image' => 'f-field.jpg'],
            ['title' => 'شراب بارد أو ساخن', 'description' => 'يختار مشروبه المفضّل من قائمة القهوة.', 'value' => 'حتى ١٫٩٠٠ ر.ع', 'image' => 'f-drink.jpg'],
        ];
    @endphp

    <header class="bg-[#0b3527] text-[#fff8e8]">
        <div aria-hidden="true" class="h-1.5 bg-[linear-gradient(to_right,#c8102e_33.33%,#fff_33.33%,#fff_66.67%,#1e7b3a_66.67%)]"></div>
        <div class="relative isolate mx-auto grid min-h-[clamp(28rem,120vw,35rem)] max-w-6xl grid-rows-[auto_1fr] overflow-hidden md:my-7 md:min-h-[34rem] md:rounded-3xl">
            <img src="{{ asset('images/national-day-56/h-drink.jpg') }}" alt="موهيتو التوت في كوب قهوة بيرحاء" fetchpriority="high" class="absolute inset-0 -z-20 size-full object-cover object-[center_42%] md:object-[center_49%]">
            <div aria-hidden="true" class="absolute inset-0 -z-10 bg-[linear-gradient(180deg,#08271fbd_0%,transparent_27%,transparent_43%,#082b22db_76%,#0b3527_100%)] md:bg-[linear-gradient(270deg,#082b22_0%,#082b22eb_27%,#082b2266_58%,transparent_100%)]"></div>
            <div class="flex items-center justify-between gap-4 px-5 pt-4 sm:px-8 md:pt-6">
                <span class="inline-flex items-center gap-2 font-bold">
                    <img src="{{ asset('images/national-day-56/g-emblem.png') }}" alt="" width="40" height="40" class="size-10 object-contain">
                    قهوة بيرحاء
                </span>
                <span class="rounded-full border border-[#e2c47a]/60 bg-[#0b3527]/75 px-3 py-1 text-xs text-[#f4d992] sm:text-sm">العيد الوطني ٥٦</span>
            </div>
            <div class="self-end px-5 pt-32 pb-5 text-center md:self-center md:px-12 md:py-12 md:text-start">
                <p class="mb-3 inline-block rounded-lg bg-[#b8112b] px-4 py-2 text-xl font-bold leading-tight sm:text-2xl">لأول ٥٦ مشتركًا فقط</p>
                <h1 class="font-heading text-[clamp(2.3rem,9vw,4.75rem)] leading-[1.08] font-bold"><span class="block text-[1.47em] text-[#e2c47a]">٦ هدايا</span> مجانًا لابنك</h1>
                <a href="{{ $assistantUrl }}" target="_blank" rel="noopener noreferrer" class="{{ $ctaClass }} mt-5 w-full max-w-xs md:w-auto">راسل المساعد الذكي</a>
            </div>
        </div>
        <p class="mx-auto max-w-2xl px-5 pt-2 pb-7 text-center text-base leading-relaxed text-[#e7eee7] sm:text-lg">اشحن محفظته بـ<strong class="text-[#f4d992]">٣٠ ر.ع</strong>… ويبقى الرصيد كاملًا له.</p>
    </header>

    <div>
        <section id="national-day-gifts" aria-labelledby="gifts-title" class="mx-auto max-w-6xl scroll-mt-6 px-5 py-12 sm:px-8 md:py-16">
            <h2 id="gifts-title" class="mb-7 font-heading text-3xl font-bold sm:text-4xl">ماذا يأخذ ابنك؟</h2>
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($gifts as $gift)
                    <li class="relative flex flex-col overflow-hidden rounded-2xl border border-[#e0d8c5] bg-[#fffaf0]">
                        @if ($gift['image'])
                            <img src="{{ asset('images/national-day-56/'.$gift['image']) }}" alt="" loading="lazy" decoding="async" class="aspect-[16/10] w-full object-cover">
                        @else
                            <div aria-hidden="true" class="grid aspect-[16/10] place-items-center bg-[#164634] text-[#e2c47a]">
                                <svg class="size-20" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m5 26 27-13 27 13-27 13L5 26Zm11 6v13c10 9 22 9 32 0V32m11-6v18"/>
                                </svg>
                            </div>
                        @endif
                        <span aria-hidden="true" class="absolute start-3 top-3 grid size-9 place-items-center rounded-full bg-[#0b3527] font-bold text-[#e2c47a]">{{ ['١', '٢', '٣', '٤', '٥', '٦'][$loop->index] }}</span>
                        <div class="flex grow flex-col items-start p-5">
                            <h3 class="font-heading text-xl font-bold">{{ $gift['title'] }}</h3>
                            <p class="mt-1 leading-relaxed text-[#42594c]">{{ $gift['description'] }}</p>
                            <p class="mt-3 text-sm font-bold text-[#793520]">وفّر {{ $gift['value'] }}</p>
                            <a href="{{ $assistantUrl }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex min-h-11 items-center font-bold text-[#0b3527] underline decoration-[#9e7838] underline-offset-4 focus-visible:outline-2" aria-label="استفسر من المساعد الذكي عن {{ $gift['title'] }}">اسأل المساعد الذكي</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>

        <section aria-labelledby="offer-steps" class="bg-[#0b3527] px-5 py-10 text-center text-[#fff8e8]">
            <h2 id="offer-steps" class="font-heading text-3xl font-bold">٣ خطوات فقط</h2>
            <ol class="mx-auto mt-6 grid max-w-3xl gap-2 sm:grid-cols-3">
                <li class="rounded-xl border border-white/20 px-4 py-3">١- سجّل رقمك</li>
                <li class="rounded-xl border border-white/20 px-4 py-3">٢- أضف ابنك</li>
                <li class="rounded-xl border border-white/20 px-4 py-3">٣- اشحن ٣٠ ر.ع</li>
            </ol>
            <a href="{{ $assistantUrl }}" target="_blank" rel="noopener noreferrer" class="{{ $ctaClass }} mt-6">ابدأ مع المساعد الذكي</a>
            <p class="mt-3 text-sm text-[#d6e6dc]">دفع آمن عبر بوابة ثواني</p>
        </section>

        <section aria-labelledby="offer-details" class="bg-[#e8dcc0]">
            <div class="mx-auto flex max-w-6xl flex-col items-start gap-5 px-5 py-9 sm:px-8 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 id="offer-details" class="font-heading text-3xl font-bold">احسبها بذكاء</h2>
                    <p class="mt-3 text-lg font-bold">تودع ٣٠ ر.ع وتبقى رصيدًا لابنك، وفوقها هدايا تصل قيمتها إلى ٧٤٫٩٠٠ ر.ع.</p>
                    <ul class="mt-3 list-inside list-disc leading-relaxed">
                        <li>تخفيض دائم للمشتركين يصل إلى ٤٠٪</li>
                        <li>بيئة آمنة ومحفّزة لابنك</li>
                    </ul>
                    <p class="mt-4 max-w-2xl leading-relaxed">للطلاب من الصف السابع إلى الثاني عشر، بحسب أسبقية الإيداع المؤكّد. اسأل المساعد الذكي عن التوفّر ومدة الاستفادة من الهدايا.</p>
                </div>
                <a href="{{ $assistantUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-12 shrink-0 items-center rounded-xl bg-[#0b3527] px-6 font-bold text-[#fff8e8] hover:bg-[#164634] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#0b3527]">استفسر من المساعد الذكي</a>
            </div>
        </section>
    </div>
</div>
@endsection

@section('whatsapp-assistant')
    <x-whatsapp-floating-button :whatsapp-url="$assistantUrl" always-visible />
@endsection
