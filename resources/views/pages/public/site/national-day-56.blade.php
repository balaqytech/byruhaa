<!doctype html>
<html lang="ar" dir="rtl">
<head>
    @include('partials.head', ['title' => 'عرض العيد الوطني ٥٦'])
    <meta name="description" content="ست هدايا من قهوة بيرحاء بمناسبة العيد الوطني ٥٦. تعرّف على تفاصيل العرض.">
    <meta name="robots" content="noindex">
</head>
<body class="bg-[#f6f1e6] text-[#173729] antialiased">
    @php
        $gifts = [
            ['title' => 'دورة: كيف أحفظ القرآن؟', 'description' => 'خطة تساعده على الحفظ والمراجعة.', 'value' => '٣٠ ر.ع', 'image' => 'f-quran.jpg'],
            ['title' => 'كتاب تلقين الصبيان', 'description' => 'طبعة سدرة من كتاب الإمام السالمي في فقه العبادات.', 'value' => '٨ ر.ع', 'image' => 'f-book.jpg'],
            ['title' => 'دورة: أسرار التفوق الدراسي', 'description' => 'مهارات عملية للمذاكرة وتنظيم الوقت.', 'value' => '٣٠ ر.ع', 'image' => null],
            ['title' => 'تذاكر سينما بيرحاء', 'description' => 'تجربة سينمائية سباعية الأبعاد.', 'value' => '٢ ر.ع', 'image' => 'f-cinema.jpg'],
            ['title' => 'ملعب بيرحاء', 'description' => 'وقت للعب والمنافسة مع الأصدقاء.', 'value' => '٣ ر.ع', 'image' => 'f-field.jpg'],
            ['title' => 'مشروب بارد أو ساخن', 'description' => 'يختار مشروبه المفضل من قائمة القهوة.', 'value' => 'حتى ١٫٩٠٠ ر.ع', 'image' => 'f-drink.jpg'],
        ];
    @endphp

    <header class="bg-[#0b3527] text-[#fff8e8]">
        <div aria-hidden="true" class="h-1.5 bg-[linear-gradient(to_right,#c8102e_33.33%,#fff_33.33%,#fff_66.67%,#1e7b3a_66.67%)]"></div>
        <div class="mx-auto max-w-6xl px-5 sm:px-8">
            <div class="flex items-center justify-between gap-4 border-b border-white/20 py-4">
                <a href="{{ route('coffee') }}" class="inline-flex items-center gap-3 font-bold">
                    <img src="{{ asset('logo-dark.png') }}" alt="" width="40" height="40" class="size-10 object-contain">
                    قهوة بيرحاء
                </a>
                <span class="text-sm text-[#e2c47a]">العيد الوطني ٥٦</span>
            </div>
            <div class="grid items-center gap-8 pt-9 pb-8 md:grid-cols-[1fr_0.85fr] md:gap-14 md:py-14">
                <div>
                    <p class="mb-3 text-sm font-bold text-[#e2c47a]">لأول ٥٦ مشتركًا</p>
                    <h1 class="font-heading text-5xl leading-tight font-bold sm:text-6xl lg:text-7xl"><span class="block text-[#e2c47a]">٦ هدايا</span> مجانًا لابنك</h1>
                    <p class="mt-5 max-w-md text-lg leading-relaxed text-[#e7eee7]">اشحن محفظة ابنك بـ٣٠ ر.ع، ويبقى المبلغ كاملًا في رصيده. وتصل قيمة الهدايا إلى ٧٤٫٩٠٠ ر.ع.</p>
                    <a href="#national-day-gifts" class="mt-6 inline-flex min-h-12 items-center rounded-xl bg-[#e2c47a] px-6 font-bold text-[#0b3527] hover:bg-[#f2d88b] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#e2c47a]">تعرّف على الهدايا</a>
                </div>
                <img src="{{ asset('images/national-day-56/h-drink.jpg') }}" alt="موهيتو التوت في كوب قهوة بيرحاء" fetchpriority="high" class="aspect-[4/3] w-full rounded-3xl object-cover object-center md:aspect-[4/5]">
            </div>
        </div>
    </header>

    <main>
        <section id="national-day-gifts" aria-labelledby="gifts-title" class="mx-auto max-w-6xl scroll-mt-6 px-5 py-12 sm:px-8 md:py-16">
            <h2 id="gifts-title" class="mb-7 font-heading text-3xl font-bold sm:text-4xl">هدايا العرض</h2>
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($gifts as $gift)
                    <li class="overflow-hidden rounded-2xl border border-[#e0d8c5] bg-[#fffaf0]">
                        @if ($gift['image'])
                            <img src="{{ asset('images/national-day-56/'.$gift['image']) }}" alt="" loading="lazy" decoding="async" class="aspect-[16/10] w-full object-cover">
                        @else
                            <div aria-hidden="true" class="grid aspect-[16/10] place-items-center bg-[#164634] text-[#e2c47a]">
                                <svg class="size-20" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m5 26 27-13 27 13-27 13L5 26Zm11 6v13c10 9 22 9 32 0V32m11-6v18"/>
                                </svg>
                            </div>
                        @endif
                        <div class="p-5">
                            <h3 class="font-heading text-xl font-bold">{{ $gift['title'] }}</h3>
                            <p class="mt-1 leading-relaxed text-[#42594c]">{{ $gift['description'] }}</p>
                            <p class="mt-3 text-sm font-bold text-[#793520]">قيمة الهدية {{ $gift['value'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>

        <section aria-labelledby="offer-details" class="bg-[#e8dcc0]">
            <div class="mx-auto flex max-w-6xl flex-col items-start gap-5 px-5 py-9 sm:px-8 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 id="offer-details" class="font-heading text-2xl font-bold">تفاصيل العرض</h2>
                    <p class="mt-2 max-w-2xl leading-relaxed">للطلبة من الصف السابع إلى الثاني عشر، بحسب أسبقية الإيداع المؤكّد. يُرجى التواصل مع بيرحاء للاستفسار عن التوفر ومدة الاستفادة من الهدايا.</p>
                </div>
                <a href="{{ route('contact') }}" class="inline-flex min-h-12 shrink-0 items-center rounded-xl bg-[#0b3527] px-6 font-bold text-[#fff8e8] hover:bg-[#164634] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#0b3527]">استفسر عن العرض</a>
            </div>
        </section>
    </main>
</body>
</html>
