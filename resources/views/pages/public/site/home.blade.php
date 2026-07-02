@php
    $eventSlug = 'your-guide-to-life-after-school';
    $eventParam = $event instanceof \App\Models\Event ? $event : $eventSlug;
    $eventUrl = route('events.show', $eventParam);
    $customerEventUrl = route('customer.events.show', $eventParam);
    $heroImage = asset('images/life-after-school-hero.png');
    $totalCapacity = $event?->seat_capacity ?? 40;
    $remainingSeats = (int) ($remainingSeats ?? $totalCapacity);
    $bookedSeats = max(0, $totalCapacity - $remainingSeats);
    $firstBatchRemaining = max(0, min(10, 10 - $bookedSeats));
    $firstBatchPercent = $firstBatchRemaining * 10;
    $arabicDigits = ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩'];
    $toArabicNumber = fn (int $number): string => strtr((string) $number, $arabicDigits);
    $totalCapacityLabel = $toArabicNumber($totalCapacity);
    $firstBatchRemainingLabel = $toArabicNumber($firstBatchRemaining);
@endphp

@extends('layouts.public')

@section('content')
    <section class="relative isolate overflow-hidden bg-[#f4f8f7] dark:bg-[#07120f]">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_12%_18%,rgba(14,124,123,0.18),transparent_32%),radial-gradient(circle_at_78%_10%,rgba(183,137,43,0.18),transparent_30%)] dark:bg-[radial-gradient(circle_at_14%_16%,rgba(25,166,164,0.16),transparent_32%),radial-gradient(circle_at_80%_10%,rgba(221,179,90,0.12),transparent_30%)]"></div>

        <div class="mx-auto grid min-h-[calc(100dvh-5rem)] w-full max-w-7xl items-center gap-10 px-4 py-10 sm:px-6 lg:grid-cols-[minmax(0,0.95fr)_minmax(420px,0.9fr)] lg:px-8 lg:py-16">
            <div class="public-hero-copy max-w-4xl">
                <p class="inline-flex min-h-10 items-center gap-2 rounded-sm border border-[#0e7c7b]/18 bg-white/68 px-3 py-2 text-sm font-bold text-[#0e7c7b] shadow-sm shadow-[#16263f]/5 dark:border-[#ddb35a]/22 dark:bg-white/8 dark:text-[#ddb35a]">
                    <x-hugeicon name="student" class="text-lg" />
                    <span>دليلك إلى الحياة بعد المدرسة</span>
                </p>

                <h1 class="mt-6 max-w-4xl font-heading text-4xl font-bold leading-tight text-[#16263f] sm:text-5xl lg:text-6xl dark:text-[#f7f1df]">
                    بعد الثاني عشر، الطريق يبدأ من هنا
                </h1>

                <p class="mt-6 max-w-2xl text-lg leading-9 text-[#16263f]/72 dark:text-[#f7f1df]/72">
                    ثلاثة أيام تساعد الخريج على فهم خياراته، استعادة ثقته، والعودة بخطة تسعين يومًا للحياة بعد المدرسة.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ $customerEventUrl }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm bg-[#0e7c7b] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#0e7c7b]/20 transition hover:-translate-y-0.5 hover:bg-[#0b6968] active:translate-y-0 dark:bg-[#ddb35a] dark:text-[#07120f] dark:hover:bg-[#f0c96a]">
                        <span>احجز الآن</span>
                        <x-hugeicon name="check-list" class="text-lg" />
                    </a>

                    <a href="{{ $eventUrl }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm border border-[#0e7c7b]/22 bg-white/64 px-6 py-3 text-sm font-bold text-[#0e7c7b] shadow-sm shadow-[#16263f]/5 transition hover:-translate-y-0.5 hover:border-[#0e7c7b]/40 hover:bg-white active:translate-y-0 dark:border-[#ddb35a]/26 dark:bg-white/8 dark:text-[#ddb35a] dark:hover:bg-white/12">
                        <span>تفاصيل الفعالية</span>
                        <x-hugeicon name="arrow-left-02" class="text-lg" />
                    </a>
                </div>
            </div>

            <aside class="public-card overflow-hidden rounded-sm border border-[#0e7c7b]/14 bg-white/78 shadow-2xl shadow-[#16263f]/12 dark:border-white/10 dark:bg-white/8 dark:shadow-black/30">
                <div class="relative aspect-[4/3] overflow-hidden">
                    <img src="{{ $heroImage }}" alt="حلقة تعليمية خارجية في مخيم عماني" width="1792" height="1024" fetchpriority="high" class="h-full w-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#07120f]/68 via-transparent to-transparent"></div>
                    <div class="absolute bottom-4 right-4 left-4 grid gap-3 rounded-sm border border-white/12 bg-[#07120f]/62 p-4 text-white backdrop-blur-md">
                        <div class="flex items-center justify-between gap-4">
                            <span class="text-sm font-semibold text-white/72">٦-٨ أغسطس ٢٠٢٦</span>
                            <span class="inline-flex items-center gap-2 rounded-sm bg-[#ddb35a] px-3 py-1.5 text-xs font-bold text-[#21170a]">
                                <x-hugeicon name="checkmark-circle-01" class="text-base" />
                                الحجز مفتوح
                            </span>
                        </div>
                        <p class="font-heading text-2xl font-bold leading-tight">إبراء، سلطنة عمان</p>
                    </div>
                </div>

                <div class="grid gap-3 p-4 sm:grid-cols-3">
                    <div class="rounded-sm bg-[#0e7c7b]/8 p-4 dark:bg-white/8">
                        <p class="text-xs font-semibold text-[#16263f]/56 dark:text-[#f7f1df]/58">المقاعد</p>
                        <p class="mt-2 font-heading text-3xl font-bold text-[#16263f] dark:text-[#f7f1df]">{{ $totalCapacityLabel }}</p>
                    </div>
                    <div class="rounded-sm bg-[#b7892b]/10 p-4 dark:bg-[#ddb35a]/12">
                        <p class="text-xs font-semibold text-[#16263f]/56 dark:text-[#f7f1df]/58">الباكورة</p>
                        <p class="mt-2 font-heading text-3xl font-bold text-[#16263f] dark:text-[#f7f1df]">٤٥</p>
                    </div>
                    <div class="rounded-sm bg-[#16263f]/8 p-4 dark:bg-white/8">
                        <p class="text-xs font-semibold text-[#16263f]/56 dark:text-[#f7f1df]/58">العمر</p>
                        <p class="mt-2 font-heading text-3xl font-bold text-[#16263f] dark:text-[#f7f1df]">١٧-١٨</p>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <section class="mx-auto grid w-full max-w-7xl gap-5 px-4 py-16 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8 lg:py-24">
        <div class="public-card border border-[#0e7c7b]/12 bg-white/72 p-6 shadow-sm shadow-[#16263f]/5 dark:border-white/10 dark:bg-white/8 dark:shadow-black/20">
            <div class="mb-5 flex size-12 items-center justify-center rounded-sm bg-[#0e7c7b]/10 text-[#0e7c7b] dark:bg-[#ddb35a]/12 dark:text-[#ddb35a]">
                <x-hugeicon name="sparkles" class="text-2xl" />
            </div>
            <h2 class="font-heading text-3xl font-bold leading-tight text-[#16263f] dark:text-[#f7f1df]">
                النجاح أوسع من معدل، والطريق أرحب من خيار واحد
            </h2>
            <p class="mt-5 text-base leading-8 text-[#16263f]/68 dark:text-[#f7f1df]/68">
                هذه الرحلة لا تبيع وعدًا جاهزًا. هي مساحة منظّمة يرى فيها الخريج نفسه، ويفهم مسارات الجامعة والتقنية والريادة والتعلم الذاتي، ثم يختار أول خطوة عملية بيده.
            </p>
        </div>

        <div class="grid gap-5 md:grid-cols-2">
            <article class="public-card border border-[#0e7c7b]/12 bg-white/72 p-6 shadow-sm shadow-[#16263f]/5 dark:border-white/10 dark:bg-white/8 dark:shadow-black/20">
                <x-hugeicon name="user-group" class="text-3xl text-[#0e7c7b] dark:text-[#ddb35a]" />
                <h3 class="mt-5 font-heading text-2xl font-bold text-[#16263f] dark:text-[#f7f1df]">بعيني ولي الأمر</h3>
                <p class="mt-3 text-sm leading-7 text-[#16263f]/66 dark:text-[#f7f1df]/66">
                    ينتهي الصف الثاني عشر، وتبدأ أسئلة المصير. هنا يجد ابنك صحبة آمنة، وإطارًا واضحًا، ومخرجًا عمليًا لا يتركه وحده أمام الحيرة.
                </p>
            </article>

            <article class="public-card border border-[#16263f]/12 bg-[#16263f] p-6 text-white shadow-lg shadow-[#16263f]/18 dark:border-white/10 dark:bg-white/8">
                <x-hugeicon name="book-open-text" class="text-3xl text-[#ddb35a]" />
                <h3 class="mt-5 font-heading text-2xl font-bold">بعيني الخريج</h3>
                <p class="mt-3 text-sm leading-7 text-white/72 dark:text-[#f7f1df]/68">
                    لن تُختصر قصتك في رقم. ستكتشف ميولك، وتسمع عن مسارات لم تُعرض عليك بوضوح، وتخرج بخطة تسعين يومًا تبدأ بعد الرحلة مباشرة.
                </p>
            </article>
        </div>
    </section>

    <section class="bg-white/62 py-16 dark:bg-white/5 lg:py-24">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <h2 class="font-heading text-4xl font-bold leading-tight text-[#16263f] lg:text-5xl dark:text-[#f7f1df]">
                    خمس محطات، وخريطة واحدة يحملها معه
                </h2>
                <p class="mt-5 max-w-2xl text-base leading-8 text-[#16263f]/66 dark:text-[#f7f1df]/66">
                    لا نعتمد على محاضرات طويلة. كل محطة تتحول إلى تجربة أو تمرين أو قرار مكتوب.
                </p>
            </div>

            <div class="mt-10 grid grid-flow-row-dense gap-4 md:grid-cols-4">
                <article class="public-card group overflow-hidden rounded-sm border border-[#0e7c7b]/12 bg-[#16263f] text-white shadow-lg shadow-[#16263f]/12 md:col-span-2">
                    <div class="aspect-[16/9] overflow-hidden">
                        <img src="{{ $heroImage }}" alt="طلاب يستمعون إلى مرشد في بيئة خارجية" loading="lazy" width="1792" height="1024" class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                    </div>
                    <div class="p-6">
                        <h3 class="font-heading text-2xl font-bold">اكتشاف الذات والميول</h3>
                        <p class="mt-3 text-sm leading-7 text-white/72">يبدأ الخريج من الداخل: ما الذي يجيده؟ ما الذي يميل إليه؟ وما القوة التي يمكن أن يبني عليها؟</p>
                    </div>
                </article>

                <article class="public-card rounded-sm border border-[#0e7c7b]/12 bg-white p-6 shadow-sm shadow-[#16263f]/5 md:col-span-2 dark:border-white/10 dark:bg-white/8 dark:shadow-black/20">
                    <x-hugeicon name="map-pin" class="text-3xl text-[#0e7c7b] dark:text-[#ddb35a]" />
                    <h3 class="mt-5 font-heading text-2xl font-bold text-[#16263f] dark:text-[#f7f1df]">خرائط المسارات</h3>
                    <p class="mt-3 text-sm leading-7 text-[#16263f]/66 dark:text-[#f7f1df]/66">الجامعي، التقني المهني، الريادي، والتعلم الذاتي. أربعة أبواب واضحة بدل باب واحد يُفرض على الجميع.</p>
                </article>

                <article class="public-card rounded-sm border border-[#0e7c7b]/12 bg-[#e4f1f0] p-6 shadow-sm shadow-[#16263f]/5 dark:border-white/10 dark:bg-[#0e7c7b]/16 dark:shadow-black/20">
                    <x-hugeicon name="computer" class="text-3xl text-[#0e7c7b] dark:text-[#7be0dd]" />
                    <h3 class="mt-5 font-heading text-xl font-bold text-[#16263f] dark:text-[#f7f1df]">مهارات المستقبل</h3>
                    <p class="mt-3 text-sm leading-7 text-[#16263f]/66 dark:text-[#f7f1df]/66">ما يحتاجه سوق العمل فعلًا: تواصل، مبادرة، تعلم سريع، وانضباط.</p>
                </article>

                <article class="public-card rounded-sm border border-[#0e7c7b]/12 bg-white p-6 shadow-sm shadow-[#16263f]/5 dark:border-white/10 dark:bg-white/8 dark:shadow-black/20">
                    <x-hugeicon name="checkmark-badge-01" class="text-3xl text-[#b7892b] dark:text-[#ddb35a]" />
                    <h3 class="mt-5 font-heading text-xl font-bold text-[#16263f] dark:text-[#f7f1df]">الدعم النفسي</h3>
                    <p class="mt-3 text-sm leading-7 text-[#16263f]/66 dark:text-[#f7f1df]/66">إعادة تعريف النتيجة المدرسية كمرحلة، لا حكم نهائي على الإنسان.</p>
                </article>

                <article class="public-card rounded-sm border border-[#b7892b]/20 bg-[#faf2dd] p-6 shadow-sm shadow-[#16263f]/5 md:col-span-2 dark:border-[#ddb35a]/18 dark:bg-[#ddb35a]/12 dark:shadow-black/20">
                    <x-hugeicon name="file-view" class="text-3xl text-[#997300] dark:text-[#ddb35a]" />
                    <h3 class="mt-5 font-heading text-2xl font-bold text-[#16263f] dark:text-[#f7f1df]">خطة التسعين يومًا</h3>
                    <p class="mt-3 text-sm leading-7 text-[#16263f]/70 dark:text-[#f7f1df]/70">لا يغادر الخريج بكلام جميل فقط. يغادر بخطوات مكتوبة، ومواعيد، ومتابعة لما بعد الرحلة.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="bg-[#0e1a2c] py-16 text-white dark:bg-[#07120f] lg:py-24">
        <div class="mx-auto grid w-full max-w-7xl gap-10 px-4 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8">
            <div>
                <h2 class="font-heading text-4xl font-bold leading-tight lg:text-5xl">لوحة المقاعد</h2>
                <p class="mt-5 max-w-xl text-base leading-8 text-white/68">
                    المقاعد تُطرح على دفعات تصاعدية. الدفعة الأولى هي الأدنى سعرًا، وحين تنفد لا يعود سعرها.
                </p>

                <div class="mt-8 rounded-sm border border-[#ddb35a]/26 bg-[#ddb35a]/10 p-5">
                    <p class="text-sm leading-7 text-[#f4dfb2]">
                        السعة الكلية أربعون مقعدًا، منها ثمانية مقاعد رحمة مدعومة لمن تحول ظروفه دون الرسوم.
                    </p>
                </div>
            </div>

            <div class="public-card rounded-sm border border-white/10 bg-white/7 p-4 shadow-2xl shadow-black/24">
                <div class="grid gap-3">
                    @foreach ([['الباكورة', '١٠', '٤٥', 'مفتوحة'], ['المتقدمة', '١٠', '٥٥', 'قادمة'], ['القياسية', '٨', '٦٥', 'قادمة'], ['الختامية', '٤', '٧٥', 'قادمة']] as [$batch, $seats, $price, $state])
                        <div class="grid gap-3 rounded-sm border border-white/10 bg-white/7 p-4 sm:grid-cols-[1.15fr_0.7fr_0.7fr_0.8fr] sm:items-center">
                            <div>
                                <p class="font-heading text-xl font-bold">{{ $batch }}</p>
                                <p class="mt-1 text-xs text-white/52">دفعة سعرية محدودة</p>
                            </div>
                            <div class="text-sm text-white/62">
                                <span class="font-bold text-white">{{ $seats }}</span>
                                مقاعد
                            </div>
                            <div class="font-heading text-2xl font-bold text-[#ddb35a]">
                                {{ $price }}
                                <span class="text-sm text-white/54">ر.ع</span>
                            </div>
                            <div>
                                <span class="inline-flex items-center gap-2 rounded-sm px-3 py-1.5 text-xs font-bold {{ $loop->first ? 'bg-[#ddb35a] text-[#21170a]' : 'bg-white/8 text-white/68' }}">
                                    <x-hugeicon :name="$loop->first ? 'checkmark-circle-01' : 'clock-01'" class="text-base" />
                                    {{ $state }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 rounded-sm border border-[#ddb35a]/24 bg-[#ddb35a]/10 p-5">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="text-sm text-[#f4dfb2]">المتبقي في دفعة الباكورة</p>
                            <p class="mt-2 font-heading text-4xl font-bold">{{ $firstBatchRemainingLabel }} <span class="text-lg text-white/58">من ١٠</span></p>
                        </div>
                        <a href="{{ $customerEventUrl }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-sm bg-[#ddb35a] px-5 py-2.5 text-sm font-bold text-[#21170a] transition hover:-translate-y-0.5 hover:bg-[#f0c96a] active:translate-y-0">
                            <span>احجز الآن</span>
                            <x-hugeicon name="check-list" class="text-lg" />
                        </a>
                    </div>
                    <div class="mt-5 h-2 overflow-hidden rounded-sm bg-white/10">
                        <div class="h-full rounded-sm bg-[#ddb35a]" style="width: {{ $firstBatchPercent }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            <article class="public-card border border-[#0e7c7b]/12 bg-white/72 p-6 shadow-sm shadow-[#16263f]/5 dark:border-white/10 dark:bg-white/8 dark:shadow-black/20">
                <x-hugeicon name="calendar-03" class="text-3xl text-[#0e7c7b] dark:text-[#ddb35a]" />
                <h3 class="mt-5 font-heading text-2xl font-bold text-[#16263f] dark:text-[#f7f1df]">الموعد</h3>
                <p class="mt-3 text-sm leading-7 text-[#16263f]/66 dark:text-[#f7f1df]/66">٦-٨ أغسطس ٢٠٢٦، ثلاثة أيام مركزة قابلة للتمديد عند الطلب.</p>
            </article>

            <article class="public-card border border-[#0e7c7b]/12 bg-white/72 p-6 shadow-sm shadow-[#16263f]/5 dark:border-white/10 dark:bg-white/8 dark:shadow-black/20">
                <x-hugeicon name="map-pin" class="text-3xl text-[#0e7c7b] dark:text-[#ddb35a]" />
                <h3 class="mt-5 font-heading text-2xl font-bold text-[#16263f] dark:text-[#f7f1df]">المكان</h3>
                <p class="mt-3 text-sm leading-7 text-[#16263f]/66 dark:text-[#f7f1df]/66">مخيم بيرحاء في إبراء، تجربة داخل عمان بلا عناء سفر خارجي.</p>
            </article>

            <article class="public-card border border-[#0e7c7b]/12 bg-white/72 p-6 shadow-sm shadow-[#16263f]/5 dark:border-white/10 dark:bg-white/8 dark:shadow-black/20">
                <x-hugeicon name="student" class="text-3xl text-[#0e7c7b] dark:text-[#ddb35a]" />
                <h3 class="mt-5 font-heading text-2xl font-bold text-[#16263f] dark:text-[#f7f1df]">لمن؟</h3>
                <p class="mt-3 text-sm leading-7 text-[#16263f]/66 dark:text-[#f7f1df]/66">خريجو الصف الثاني عشر من الفتيان، خاصة من يبحث عن اتجاه واضح بعد النتائج.</p>
            </article>

            <article class="public-card border border-[#b7892b]/20 bg-[#faf2dd] p-6 shadow-sm shadow-[#16263f]/5 dark:border-[#ddb35a]/18 dark:bg-[#ddb35a]/12 dark:shadow-black/20">
                <x-hugeicon name="coupon-percent" class="text-3xl text-[#997300] dark:text-[#ddb35a]" />
                <h3 class="mt-5 font-heading text-2xl font-bold text-[#16263f] dark:text-[#f7f1df]">خصومات</h3>
                <p class="mt-3 text-sm leading-7 text-[#16263f]/70 dark:text-[#f7f1df]/70">خصم الإخوة خمسة بالمئة، وخصم المجموعة ثمانية بالمئة لثلاثة مشاركين فأكثر.</p>
            </article>
        </div>
    </section>

    <section class="bg-white/62 py-16 dark:bg-white/5 lg:py-24" id="booking">
        <div class="mx-auto grid w-full max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[0.85fr_1.15fr] lg:px-8">
            <div>
                <h2 class="font-heading text-4xl font-bold leading-tight text-[#16263f] lg:text-5xl dark:text-[#f7f1df]">
                    ابدأ الحجز من صفحة الفعالية
                </h2>
                <p class="mt-5 max-w-xl text-base leading-8 text-[#16263f]/66 dark:text-[#f7f1df]/66">
                    مسار الحجز الرسمي موجود في حساب العميل. يمكنك مراجعة تفاصيل الفعالية أولًا، أو الانتقال مباشرة إلى صفحة الحجز.
                </p>
            </div>

            <form method="GET" action="{{ $customerEventUrl }}" class="public-card border border-[#0e7c7b]/12 bg-white/82 p-6 shadow-xl shadow-[#16263f]/8 dark:border-white/10 dark:bg-white/8 dark:shadow-black/24">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-sm border border-[#0e7c7b]/12 bg-[#f6fbf8] p-4 dark:border-white/10 dark:bg-white/8">
                        <p class="text-xs font-semibold text-[#16263f]/54 dark:text-[#f7f1df]/54">رقم الفعالية</p>
                        <p class="mt-2 font-heading text-2xl font-bold text-[#16263f] dark:text-[#f7f1df]">٢</p>
                    </div>
                    <div class="rounded-sm border border-[#0e7c7b]/12 bg-[#f6fbf8] p-4 dark:border-white/10 dark:bg-white/8">
                        <p class="text-xs font-semibold text-[#16263f]/54 dark:text-[#f7f1df]/54">الرابط</p>
                        <p class="mt-2 truncate text-sm font-semibold text-[#0e7c7b] dark:text-[#ddb35a]">{{ $eventSlug }}</p>
                    </div>
                </div>

                <div class="mt-5 rounded-sm border border-dashed border-[#0e7c7b]/22 p-5 dark:border-[#ddb35a]/24">
                    <p class="text-sm leading-7 text-[#16263f]/68 dark:text-[#f7f1df]/68">
                        عند الضغط على زر الحجز ستنتقل إلى صفحة العميل الخاصة بهذه الفعالية لإكمال الطلب، إضافة بيانات المشاركين، ومتابعة القبول والسداد.
                    </p>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <button type="submit" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm bg-[#0e7c7b] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#0e7c7b]/20 transition hover:-translate-y-0.5 hover:bg-[#0b6968] active:translate-y-0 dark:bg-[#ddb35a] dark:text-[#07120f] dark:hover:bg-[#f0c96a]">
                        <span>احجز الآن</span>
                        <x-hugeicon name="check-list" class="text-lg" />
                    </button>

                    <a href="{{ $eventUrl }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm border border-[#0e7c7b]/22 px-6 py-3 text-sm font-bold text-[#0e7c7b] transition hover:-translate-y-0.5 hover:border-[#0e7c7b]/40 hover:bg-[#0e7c7b]/8 active:translate-y-0 dark:border-[#ddb35a]/26 dark:text-[#ddb35a] dark:hover:bg-[#ddb35a]/10">
                        <span>تفاصيل الفعالية</span>
                        <x-hugeicon name="arrow-left-02" class="text-lg" />
                    </a>
                </div>
            </form>
        </div>
    </section>
@endsection
