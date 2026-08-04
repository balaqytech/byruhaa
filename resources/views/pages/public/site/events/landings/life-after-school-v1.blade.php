@php
    $heroVideoUrl = 'https://player.vimeo.com/video/1215490802?badge=0&autopause=0&player_id=0&app_id=58479';
    $planWorkshopImage = asset('images/station-written-plan-workshop.png');
    $decisionPathImage = asset('images/station-decision-path.png');
    $finalCtaImage = asset('images/final-cta-first-step.png');
    $founderImage = asset('images/founder.webp');
    $totalCapacity = $event->seat_capacity;
    $remainingSeats = (int) $remainingSeats;
    $arabicDigits = [
        '0' => '٠',
        '1' => '١',
        '2' => '٢',
        '3' => '٣',
        '4' => '٤',
        '5' => '٥',
        '6' => '٦',
        '7' => '٧',
        '8' => '٨',
        '9' => '٩',
    ];
    $toArabicNumber = fn(int $number): string => strtr((string) $number, $arabicDigits);
    $formatPrice = fn(int $priceBaisa): string => number_format($priceBaisa / 1000, 3, '.', '');

    $openTier = $tierOffer['current'];
    $nextTier = $tierOffer['next'];
    $currentPrice = $openTier === null ? null : $formatPrice($openTier['price_baisa']);
    $openTierRemaining = $openTier['remaining_seats'] ?? 0;
    $tiers = collect([$openTier, $nextTier])
        ->filter()
        ->values()
        ->map(
            fn(array $tier, int $index): array => [
                ...$tier,
                'code' => $index === 0 ? 'الآن' : 'تاليًا',
                'open' => $index === 0,
            ],
        );

    $facts = [
        ['icon' => 'calendar-03', 'label' => 'تبدأ ١٣ أغسطس ٢٠٢٦م'],
        ['icon' => 'map-pin', 'label' => 'مخيم بيرحاء، إبراء، عُمان'],
        ['icon' => 'clock-01', 'label' => '٣ أيام مركزة'],
        ['icon' => 'student', 'label' => 'دورة خاصة لخريجي الصف الثاني عشر فقط'],
    ];

    $worries = [
        [
            'icon' => 'sparkles',
            'title' => 'حيرة اختيار التخصص',
            'body' => 'قائمة التخصصات طويلة، والضغط يدفع لقرار متسرّع يُندم عليه سنوات.',
            'outcome' => 'نبدأ من خريطة الذات لا من قائمة التخصصات: نكتشف ميله أولًا، ثم يختار على بصيرة.',
        ],
        [
            'icon' => 'checkmark-badge-01',
            'title' => 'وطأة المقارنة بعد النتائج',
            'body' => 'معدّل أقل من المتوقّع، ومقارنة مؤلمة تثقل القلب وتطفئ الحماسة.',
            'outcome' => 'جلسة إعادة تعريف الفشل: المعدّل رقم في ورقة، لا حكم على العمر.',
        ],
        [
            'icon' => 'clock-01',
            'title' => 'الفجوة المهدرة بعد المدرسة',
            'body' => 'أشهر تمر بلا وجهة ولا خطوة، فتذوب الطاقة في الانتظار.',
            'outcome' => 'خطة ٩٠ يومًا مكتوبة بيده تبدأ من الغد: لا فراغ يهدر، بل بناء يثمر بإذن الله.',
        ],
        [
            'icon' => 'map-pin',
            'title' => 'خوف المسار الخاطئ',
            'body' => 'ماذا لو اخترت طريقًا لا يناسبني؟ سؤال يشل القرار.',
            'outcome' => 'أربع خرائط مسارات: جامعي، تقني مهني، ريادي، وتعلّم ذاتي.',
        ],
    ];

    $stations = [
        [
            'icon' => 'student',
            'image' => asset('images/station-self-discovery.png'),
            'title' => 'اكتشاف الذات والميول',
            'body' => 'استمارة خريطة الذات: ماذا أودع الله فيك من قوّة وميل؟ نبدأ من الداخل.',
        ],
        [
            'icon' => 'map-pin',
            'image' => asset('images/station-four-paths.png'),
            'title' => 'خرائط المسارات الأربعة',
            'body' => 'الجامعي، والتقني المهني، والريادي، والتعلّم الذاتي. لا مسار واحد مقدّس.',
        ],
        [
            'icon' => 'computer',
            'image' => asset('images/station-future-skills.png'),
            'title' => 'مهارات المستقبل',
            'body' => 'ما يطلبه سوق العمل فعلًا: مهارات تتعلّم اليوم، بلا انتظار جامعة.',
        ],
        [
            'icon' => 'checkmark-badge-01',
            'image' => asset('images/station-psychological-support.png'),
            'title' => 'الدعم النفسي وإعادة تعريف الفشل',
            'body' => 'قصص ناجحين بمسارات غير تقليدية، وجلسات تفريغ ومصارحة تطمئن القلب.',
        ],
        [
            'icon' => 'file-view',
            'image' => asset('images/station-ninety-day-plan.png'),
            'title' => 'خطة التسعين يومًا',
            'body' => 'يخرج كل خرّيج بخطة مكتوبة بخط يده: قرار عملي يبدأ به من الغد.',
        ],
        [
            'icon' => 'user-group',
            'image' => asset('images/after-twelfth-mentor-circle.png'),
            'title' => 'صحبة تبقى',
            'body' => 'رفقة من الفتيان تشاركه المرحلة، ومرشدون يصغون قبل أن يجيبوا، وأثر يمتد بعد المغادرة.',
        ],
    ];

    $includedFees = [
        [
            'title' => 'التدريب ومخرجاته',
            'icon' => 'book-open-text',
            'items' => [
                ['title' => 'المقعد التدريبي كاملًا، ست محطات', 'body' => 'خريطة الذات والميول، وخرائط المسارات الأربعة: الجامعي والتقني المهني والريادي والتعلّم الذاتي، ومهارات المستقبل، والدعم النفسي وإعادة تعريف الفشل، وخطة التسعين يومًا، وصحبة تبقى.'],
                ['title' => 'قيادة مباشرة من أبي بلج', 'body' => 'عبدالله بن عامر العيسري، خبرة تربوية تتجاوز عشرين سنة، يقود المحطات بنفسه لا بالوكالة.'],
                ['title' => 'الكرّاسات السبع مطبوعة باسم المشارك', 'body' => 'يملؤها بخط يده ويعود بها في يده: خريطة الذات، وخرائط المسارات، ودليل مهارات المستقبل، وكرّاسة الدعم النفسي، وكرّاسة التسعين يومًا، وميثاق الصحبة، وحزمة ولي الأمر.'],
                ['title' => 'المخرج الموعود: خطة تسعين يومًا مكتوبة بخط يده', 'body' => 'هدف واحد محدد مقيس بموعد، وخماسية السكينة: العبادة والعلم والعمل واللعب والنوم والصحة، وعقد موقّع وشاهد عليه.'],
                ['title' => 'شهادة إتمام على المخرجات لا على الحضور', 'body' => 'تحمل اسمه واسم هدفه المكتوب. لا نصدر شهادة حضور لأن الفرق بين «حضرت» و«صنعت» هو أصل المنهج.'],
            ],
        ],
        [
            'title' => 'الإقامة والضيافة',
            'icon' => 'home-01',
            'items' => [
                ['title' => 'الإقامة داخل مخيم بيرحاء طوال الأيام الثلاثة', 'body' => 'مبيت مجهز داخل المخيم، والفعالية تمتد إلى خمسة أيام عند الطلب.'],
                ['title' => 'إفطار يومي، مائدة مفتوحة', 'body' => 'وجبة إفطار مشمولة كل صباح من أيام الفعالية، بلا حصة محدودة.'],
                ['title' => 'مشروب مجاني واحد كل يوم من مقهى المخيم', 'body' => 'يختاره القائد الملتحق بالدورة بنفسه، ساخنًا أو باردًا، قهوة أو شايًا أو غيرهما مما يقدمه المقهى. مشروب واحد لكل مشارك في كل يوم من أيام الدورة.'],
                ['title' => 'إشراف كامل ليلًا ونهارًا', 'body' => 'مشرف مؤهل لكل ثمانية فتيان كحد أقصى، التزام سلامة لا يساوم عليه.'],
            ],
        ],
        [
            'title' => 'الزيارات الميدانية',
            'icon' => 'map-pin',
            'items' => [
                ['title' => 'زيارة حديقة الحيوان «عالم سفاري»', 'body' => 'أكبر حديقة حيوان في سلطنة عمان، زيارة مشمولة بالرسوم ضمن برنامج الأيام.'],
                ['title' => 'جولة في أكبر مجمّع للقصور الأثرية في شبه الجزيرة العربية', 'body' => 'جولة ميدانية يقرأ فيها الفتى عمارة أجداده على الطبيعة لا في الكتاب.'],
                ['title' => 'زيارة قرية السباخ الأثرية', 'body' => 'قرية تراثية شاهدة على عمارة المنطقة وحياة أهلها.'],
                ['title' => 'التنقل إلى الزيارات الميدانية ذهابًا وإيابًا', 'body' => 'نقل منظم من المخيم وإليه بصحبة المشرفين، دون حاجة إلى ترتيب مواصلات لهذه الزيارات.'],
            ],
        ],
        [
            'title' => 'الاطمئنان أثناء الأيام الثلاثة',
            'icon' => 'user-group',
            'items' => [
                ['title' => 'تقرير يومي لولي الأمر', 'body' => 'يصلك مساء كل يوم عبر قناة مخصصة: ما أُنجز، وحال المجموعة، وسطر عن ابنك خاصة، وبرنامج الغد.'],
                ['title' => 'تقرير ختامي فردي عن ابنك', 'body' => 'يسلّم في اليوم الثالث: ميوله الغالبة، ومساره المختار، وهدفه، وقوته، وما يحتاج إسنادًا فيه، وتوصياتنا للأسرة.'],
            ],
        ],
    ];

    $faqs = [
        [
            'question' => 'أين تُقام الفعالية ومتى؟',
            'answer' =>
                'تُقام في مخيم بيرحاء بولاية إبراء، سلطنة عُمان. تبدأ يوم ١٣ أغسطس ٢٠٢٦م وتستمر ثلاثة أيام إقامية مركزة.',
        ],
        [
            'question' => 'كيف يُشرَف على الأبناء ليلًا ونهارًا؟',
            'answer' =>
                'الإشراف كامل على مدار الوقت، بمشرفين مؤهلين للمجموعات الصغيرة، داخل بيئة آمنة بقيم تربوية تعرفها الأسرة.',
        ],
        [
            'question' => 'كيف نتواصل مع ابننا أثناء الأيام الثلاثة؟',
            'answer' =>
                'تصلكم تقارير متابعة تطمئنكم أولًا بأول عبر قناة تواصل مخصصة، مع إتاحة التواصل المباشر في الأوقات المناسبة دون تشتيت البرنامج.',
        ],
        [
            'question' => 'بماذا يعود ابني فعليًا من هذه الأيام؟',
            'answer' => 'يعود بخريطة مسار واضحة، وبخطة ٩٠ يومًا مكتوبة بخط يده، وبثقة مستعادة في نفسه ومستقبله.',
        ],
        [
            'question' => 'ما الفرق بين هذه الفعالية ودورات التوجيه المهني التقليدية؟',
            'answer' =>
                'الدورات تلقّن معلومات، أما هذه الفعالية فتعيش تجربة. لا نبدأ من قائمة التخصصات، بل من ميل ابنك وقيمته وثقته.',
        ],
        [
            'question' => 'ما معنى التسعير المتدرج؟',
            'answer' =>
                'نطرح المقاعد على شرائح تصعد كلما اقترب الموعد ونفدت المقاعد. من بادر نال أرخصها، وحين تنفد الشريحة لا يعود سعرها.',
        ],
        [
            'question' => 'هل الفعالية للفتيان فقط؟ وما الفئة العمرية؟',
            'answer' =>
                'نعم، هذه الفعالية الإقامية مخصصة لخرّيجي الثاني عشر من الفتيان،، لطبيعة الإقامة الكاملة والإشراف.',
        ],
    ];
@endphp

@extends('layouts.public', [
    'title' => 'بعد الثاني عشر، الطريق يبدأ',
    'metaDescription' => 'فعالية إقامية لخرّيجي الثاني عشر في مخيم بيرحاء بإبراء، يغادر فيها المشارك بخطة ٩٠ يومًا مكتوبة بيده.',
])

@section('content')
    <section class="relative isolate overflow-hidden bg-[#0b1524] text-white">
        <div
            class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_85%_0%,rgba(23,163,161,0.26),transparent_36%),radial-gradient(circle_at_4%_6%,rgba(223,180,88,0.22),transparent_34%),linear-gradient(180deg,#0b1524,#0f1b2e_58%,#16263f)]">
        </div>
        <div
            class="absolute inset-x-0 bottom-0 h-1 bg-[linear-gradient(90deg,transparent,#b7892b_18%,#dfb458_50%,#b7892b_82%,transparent)]">
        </div>

        <div
            class="mx-auto grid min-h-[calc(100dvh-5rem)] w-full max-w-7xl items-center gap-10 px-4 py-8 sm:px-6 sm:py-12 lg:grid-cols-[minmax(0,1fr)_minmax(340px,0.72fr)] lg:px-8 lg:py-8">
            <div class="order-2 max-w-4xl lg:order-1">
                <div class="hidden flex-wrap items-center gap-3 text-sm text-[#9dc3c3] sm:flex">
                    <span class="font-heading text-xl font-bold text-white">بِيرُحاء</span>
                    <span>للسياحة والتجارة</span>
                    <span class="h-1.5 w-1.5 rounded-full bg-[#dfb458]"></span>
                    <span>مجموعة العيسري</span>
                </div>

                <p
                    class="mt-2 inline-flex min-h-9 items-center gap-2 rounded-sm border border-[#17a3a1]/34 bg-[#17a3a1]/14 px-3 py-2 text-xs font-bold text-[#bee9e8] sm:mt-7 sm:min-h-10 sm:px-4 sm:text-sm">
                    <x-hugeicon name="student" class="text-lg" />
                    <span>فعالية إقامية لخرّيجي الثاني عشر، للفتيان</span>
                </p>

                <h1 class="mt-4 max-w-4xl font-heading text-3xl font-bold leading-tight sm:mt-6 sm:text-5xl lg:text-6xl">
                    بعد الثاني عشر، <span class="text-[#dfb458]">الطريق يبدأ من هنا</span>
                </h1>

                <p class="mt-4 max-w-2xl text-base leading-7 text-[#cfe2e2] sm:mt-6 sm:text-lg sm:leading-9">
                    النجاح أوسع من معدّل، والطريق أرحب من خيار واحد. ثلاثة أيام تعيد لابنك يقينه، وتفتح له الأبواب.
                </p>

                <div
                    class="mt-4 flex max-w-2xl gap-3 rounded-sm border border-white/12 bg-white/6 p-4 sm:mt-7 sm:gap-4 sm:p-5">
                    <x-hugeicon name="file-view" class="mt-1 text-2xl text-[#dfb458]" />
                    <p class="text-sm leading-7 text-[#cfe2e2] sm:text-base sm:leading-8">
                        <span class="font-bold text-white">الوعد الملموس:</span>
                        يغادر ابنك بخطة ٩٠ يومًا مكتوبة بيده، قرار عملي لا كلام عام، حفظه الله.
                    </p>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-2 sm:mt-7 sm:flex sm:flex-wrap">
                    @foreach ($facts as $fact)
                        <span
                            class="inline-flex min-h-10 items-center gap-2 rounded-sm border border-white/12 bg-white/6 px-3 py-2 text-xs leading-5 text-[#dcebeb] sm:text-sm">
                            <x-hugeicon :name="$fact['icon']" class="text-base text-[#9dc3c3]" />
                            <span>{{ $fact['label'] }}</span>
                        </span>
                    @endforeach
                </div>

                <div class="mt-5 flex flex-wrap gap-3 sm:mt-8">
                    <a href="#seats"
                        class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-sm bg-[#dfb458] px-6 py-3 text-sm font-bold text-[#231703] shadow-lg shadow-[#b7892b]/30 transition hover:-translate-y-0.5 hover:bg-[#f0c96a] active:translate-y-0 sm:w-auto">
                        <span>احجز مقعد ابنك الآن</span>
                        <x-hugeicon name="arrow-left-02" class="text-lg" />
                    </a>

                    <a href="#program"
                        class="hidden min-h-12 items-center justify-center gap-2 rounded-sm border border-white/28 bg-white/6 px-6 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-white/12 active:translate-y-0 sm:inline-flex">
                        <span>ماذا يحدث في الأيام الثلاثة؟</span>
                        <x-hugeicon name="check-list" class="text-lg" />
                    </a>
                </div>
            </div>

            <aside
                class="order-1 overflow-hidden rounded-sm border border-white/12 bg-white/7 shadow-2xl shadow-black/24 lg:order-2">
                <div class="relative aspect-[9/16] overflow-hidden lg:aspect-[4/3]">
                    <iframe src="{{ $heroVideoUrl }}" class="absolute inset-0 size-full" title="مقطع دورة الثاني عشر"
                        allow="autoplay; fullscreen; picture-in-picture; clipboard-write; encrypted-media; web-share"
                        referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                </div>

                <div class="p-5">
                    <div class="rounded-sm border border-[#dfb458]/24 bg-[#dfb458]/10 p-5">
                        <p class="text-base font-bold text-[#f4dfb2]">
                            {{ $tierOffer['is_sold_out'] ? 'اكتملت المقاعد' : 'الباقة المفتوحة الآن' }}</p>
                        <p class="mt-2 font-heading text-4xl font-bold text-white sm:text-5xl">
                            {{ $openTier['name'] ?? 'اكتمل الحجز' }}</p>
                        <p class="mt-3 text-sm leading-7 text-[#cfe2e2]">المقاعد الأولى أرخص، وحين تنفد الشريحة لا يعود
                            سعرها.</p>
                    </div>

                    @if ($openTier)
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-sm bg-white/8 p-4 text-center">
                                <p class="text-sm font-bold text-[#9dc3c3]">المتبقي في هذه الباقة</p>
                                <p class="mt-2 font-heading text-5xl font-bold text-[#17a3a1]">
                                    {{ $toArabicNumber($openTierRemaining) }} <span class="text-lg">مقاعد</span></p>
                            </div>
                            <div class="rounded-sm bg-white/8 p-4 text-center">
                                <p class="text-sm font-bold text-[#9dc3c3]">السعر الحالي</p>
                                <p class="mt-2 font-heading text-5xl font-bold text-[#dfb458]">{{ $currentPrice }} <span
                                        class="text-lg">ر.ع</span></p>
                            </div>
                        </div>
                        <div class="mt-3 border-s-4 border-[#ef5b5b] bg-[#ef5b5b]/12 p-4" data-current-package-alert>
                            <p class="flex items-center gap-2 font-heading text-xl font-bold text-white">
                                <x-hugeicon name="alert-02" class="shrink-0 text-2xl text-[#ff8d8d]" />
                                <span>احجز الآن قبل نفاد الكمية</span>
                            </p>
                            <p class="mt-2 text-base font-bold text-[#f4dfb2]">بقي {{ $toArabicNumber($openTierRemaining) }}
                                مقاعد فقط بالسعر الحالي.</p>
                            @if ($nextTier)
                                <p class="mt-2 text-sm leading-7 text-[#cfe2e2]">بعد نفادها تنتقل الحجوزات إلى
                                    {{ $nextTier['name'] }} بسعر <strong
                                        class="font-heading text-xl text-white">{{ $formatPrice($nextTier['price_baisa']) }}
                                        ر.ع</strong></p>
                            @else
                                <p class="mt-2 text-sm leading-7 text-[#cfe2e2]">هذه آخر باقة متاحة للحجز.</p>
                            @endif
                        </div>
                    @endif
                </div>
            </aside>
        </div>
    </section>

    <section id="worries"
        class="bg-[linear-gradient(180deg,#ffffff,#f3f8f8)] py-16 lg:py-24 dark:bg-[linear-gradient(180deg,rgba(255,255,255,0.04),rgba(7,18,15,0.08))]">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="font-heading text-sm font-bold text-[#0e7c7b] dark:text-[#e0a800]">نفهم قلقك</p>
                <h2
                    class="mt-3 font-heading text-3xl font-bold leading-tight text-[#16263f] lg:text-5xl dark:text-[#f7f1df]">
                    أربعة هموم تسكن الخرّيج وأهله، ونفتح لكل منها مخرجًا</h2>
                <p class="mt-5 text-base leading-8 text-[#566a72] dark:text-[#f7f1df]/66">لا نهوّن من الأمر، ولا نضخّمه.
                    نضعه على الطاولة، ونمشي منه إلى حل عملي.</p>
            </div>

            <div class="mt-10 grid gap-5 md:grid-cols-2">
                @foreach ($worries as $worry)
                    <article
                        class="rounded-sm border border-[#d9e4e4] bg-white p-6 shadow-sm shadow-[#0f1b2e]/5 dark:border-white/10 dark:bg-white/8 dark:shadow-black/20">
                        <div class="flex items-start gap-4">
                            <span
                                class="flex size-11 shrink-0 items-center justify-center rounded-sm bg-[#e6f1f1] text-[#0e7c7b] dark:bg-[#e0a800]/12 dark:text-[#e0a800]">
                                <x-hugeicon :name="$worry['icon']" class="text-xl" />
                            </span>
                            <div>
                                <h3 class="font-heading text-xl font-bold text-[#16263f] dark:text-[#f7f1df]">
                                    {{ $worry['title'] }}</h3>
                                <p class="mt-3 text-sm leading-7 text-[#566a72] dark:text-[#f7f1df]/64">
                                    {{ $worry['body'] }}
                                </p>
                            </div>
                        </div>
                        <div
                            class="mt-5 border-t border-dashed border-[#d9e4e4] pt-4 text-sm leading-7 text-[#152230] dark:border-white/10 dark:text-[#f7f1df]/78">
                            <span class="font-heading font-bold text-[#0e7c7b] dark:text-[#e0a800]">المخرج:</span>
                            {{ $worry['outcome'] }}
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section id="lenses" class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
        <div class="max-w-3xl">
            <p class="font-heading text-sm font-bold text-[#0e7c7b] dark:text-[#e0a800]">برسالتين</p>
            <h2 class="mt-3 font-heading text-3xl font-bold leading-tight text-[#16263f] lg:text-5xl dark:text-[#f7f1df]">
                نخاطب الأب والابن، كل بلغته</h2>
            <p class="mt-5 text-base leading-8 text-[#566a72] dark:text-[#f7f1df]/66">قرار الأب ثقة، ويقين الابن استعادة.
                وكلاهما هدفنا في هذه الأيام.</p>
        </div>

        <div class="mt-10 grid gap-5 lg:grid-cols-2">
            <article
                class="rounded-sm bg-[linear-gradient(165deg,#16263f,#0f1b2e)] p-7 text-[#eaf2f2] shadow-xl shadow-[#16263f]/16">
                <p
                    class="inline-flex items-center gap-2 rounded-sm border border-white/20 bg-white/12 px-3 py-1.5 text-sm font-bold">
                    <x-hugeicon name="user-group" class="text-lg" />
                    بعيني ولي الأمر
                </p>
                <h3 class="mt-5 font-heading text-2xl font-bold text-white">ترى ابنك يخرج بخطة وأمل، لا بإحباط ومقارنة</h3>
                <p class="mt-4 leading-8 text-[#cfe2e2]">تسلّمه إلينا قلقًا حائرًا، فيعود إليك، حفظه الله، واثقًا يحمل بيده
                    خريطة مسار وقرارًا مكتوبًا.</p>
                <p class="mt-4 leading-8 text-[#cfe2e2]"><span class="font-bold text-[#dfb458]">إشراف كامل، وتقارير
                        تطمئنك</span> على ابنك أولًا بأول، في بيئة آمنة بقيم تعرفها.</p>
            </article>

            <article
                class="rounded-sm bg-[linear-gradient(165deg,#0e7c7b,#0b6968)] p-7 text-[#eaf7f6] shadow-xl shadow-[#0e7c7b]/16">
                <p
                    class="inline-flex items-center gap-2 rounded-sm border border-white/20 bg-white/12 px-3 py-1.5 text-sm font-bold">
                    <x-hugeicon name="student" class="text-lg" />
                    بعيني الخرّيج
                </p>
                <h3 class="mt-5 font-heading text-2xl font-bold text-white">لست متأخرًا، ولست وحدك. الطريق أمامك أوسع مما
                    تظن</h3>
                <p class="mt-4 leading-8 text-[#eaf7f6]/90">ستكتشف ما أودعه الله فيك من قوة وميل، وتلتقي من مشى الطريق قبلك
                    بمسار غير تقليدي.</p>
                <p class="mt-4 leading-8 text-[#eaf7f6]/90"><span class="font-bold text-[#dfb458]">تغادر بخطة بخط
                        يدك</span>، أول خطوة نحو حياة تختارها أنت، لا حياة تنتظرها.</p>
            </article>
        </div>
    </section>

    <section id="program"
        class="bg-[linear-gradient(180deg,#ffffff,#f3f8f8)] py-16 lg:py-24 dark:bg-[linear-gradient(180deg,rgba(255,255,255,0.04),rgba(7,18,15,0.08))]">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="font-heading text-sm font-bold text-[#0e7c7b] dark:text-[#e0a800]">محاور البرنامج</p>
                <h2
                    class="mt-3 font-heading text-3xl font-bold leading-tight text-[#16263f] lg:text-5xl dark:text-[#f7f1df]">
                    ست محطات في ثلاثة أيام</h2>
                <p class="mt-5 text-base leading-8 text-[#566a72] dark:text-[#f7f1df]/66">كل محطة تنقله خطوة: من اكتشاف
                    ذاته، إلى قرار مكتوب يبدأ به.</p>
            </div>

            <div class="mt-10 grid gap-5 sm:grid-cols-2">
                <article
                    class="overflow-hidden rounded-sm border border-[#d9e4e4] bg-white shadow-sm shadow-[#0f1b2e]/5 sm:col-span-2 dark:border-white/10 dark:bg-white/8 dark:shadow-black/20">
                    <div class="grid h-full sm:grid-cols-[minmax(180px,0.42fr)_minmax(0,1fr)]">
                        <div class="aspect-[4/3] overflow-hidden bg-[#e6f1f1] sm:aspect-auto sm:min-h-80 dark:bg-white/8">
                            <img src="{{ $planWorkshopImage }}" alt="طالب عماني يكتب خطة عملية في ورشة تعليمية"
                                width="941" height="1672" loading="lazy" class="h-full w-full object-cover">
                        </div>
                        <div class="p-6">
                            <p class="font-heading text-sm font-bold text-[#0e7c7b] dark:text-[#e0a800]">المخرج العملي</p>
                            <h3 class="mt-3 font-heading text-2xl font-bold text-[#16263f] dark:text-[#f7f1df]">خطة مكتوبة،
                                لا محاضرة عابرة</h3>
                            <p class="mt-3 text-sm leading-7 text-[#566a72] dark:text-[#f7f1df]/64">نحوّل الحوار إلى أوراق
                                عمل وخطوات يكتبها المشارك بيده، حتى يعود إلى البيت ومعه بداية واضحة.</p>
                        </div>
                    </div>
                </article>

                @foreach ($stations as $station)
                    <article
                        class="overflow-hidden rounded-sm border border-[#d9e4e4] bg-white shadow-sm shadow-[#0f1b2e]/5 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-[#0f1b2e]/8 dark:border-white/10 dark:bg-white/8 dark:shadow-black/20">
                        <div class="aspect-[4/3] overflow-hidden bg-[#e6f1f1] dark:bg-white/8">
                            <img src="{{ $station['image'] }}" alt="{{ $station['title'] }}" width="941"
                                height="1672" loading="lazy" class="h-full w-full object-cover">
                        </div>
                        <div class="p-6">
                            <p class="font-heading text-sm font-bold text-[#b7892b]">المحطة
                                {{ $toArabicNumber($loop->iteration) }}</p>
                            <span
                                class="mt-4 flex size-13 items-center justify-center rounded-sm bg-[linear-gradient(160deg,#17a3a1,#0e7c7b)] text-white">
                                <x-hugeicon :name="$station['icon']" class="text-2xl" />
                            </span>
                            <h3 class="mt-5 font-heading text-xl font-bold text-[#16263f] dark:text-[#f7f1df]">
                                {{ $station['title'] }}</h3>
                            <p class="mt-3 text-sm leading-7 text-[#566a72] dark:text-[#f7f1df]/64">{{ $station['body'] }}
                            </p>
                        </div>
                    </article>
                @endforeach

                <article
                    class="overflow-hidden rounded-sm bg-[linear-gradient(160deg,#16263f,#0f1b2e)] text-[#eaf2f2] shadow-lg shadow-[#16263f]/16 sm:col-span-2">
                    <div class="grid sm:grid-cols-[minmax(220px,0.42fr)_minmax(0,1fr)]">
                        <div class="aspect-[4/3] overflow-hidden bg-[#0f1b2e] sm:aspect-auto sm:min-h-72">
                            <img src="{{ $decisionPathImage }}" alt="طالب عماني يصل من المحطة الأولى إلى القرار"
                                width="941" height="1672" loading="lazy" class="h-full w-full object-cover">
                        </div>
                        <div class="flex flex-col justify-center p-6 text-start sm:p-8">
                            <p class="font-heading text-2xl font-bold text-[#dfb458]">من المحطة الأولى إلى القرار</p>
                            <p class="mt-3 max-w-2xl leading-8 text-[#cfe2e2]">ثلاثة أيام مركزة تنتهي بخطة واضحة قابلة
                                للتنفيذ، ويعرف المشارك ما سيبدأه في اليوم التالي.</p>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section id="leader" class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
        <div class="max-w-3xl">
            <p class="font-heading text-sm font-bold text-[#0e7c7b] dark:text-[#e0a800]">مَن يقود؟</p>
            <h2 class="mt-3 font-heading text-3xl font-bold leading-tight text-[#16263f] lg:text-5xl dark:text-[#f7f1df]">
                الثقة تسبق القرار</h2>
            <p class="mt-5 text-base leading-8 text-[#566a72] dark:text-[#f7f1df]/66">قبل أن تحجز، تعرف من يستقبل ابنك،
                وبأي خبرة وفلسفة.</p>
        </div>

        <div
            class="mt-10 overflow-hidden rounded-sm bg-[linear-gradient(165deg,#16263f,#0f1b2e)] text-[#eaf2f2] shadow-xl shadow-[#16263f]/16">
            <div class="grid items-stretch gap-0 md:grid-cols-[minmax(260px,0.42fr)_minmax(0,0.58fr)]">
                <div class="relative min-h-80 overflow-hidden">
                    <img src="{{ $founderImage }}" alt="أبو بلج، عبدالله العيسري" width="1024" height="1024"
                        loading="lazy" class="h-full w-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#0f1b2e]/62 via-transparent to-transparent"></div>
                </div>
                <div class="p-7 lg:p-9">
                    <p class="font-heading text-sm font-bold text-[#dfb458]">قيادة الفعالية</p>
                    <h3 class="mt-2 font-heading text-3xl font-bold text-white">أبو بلج، عبدالله العيسري</h3>
                    <p class="mt-2 text-[#9dc3c3]">مؤسس بيرحاء ومرجعها التربوي، مجموعة العيسري</p>
                    <p class="mt-5 max-w-3xl leading-8 text-[#cfe2e2]">خبرة تربوية تتجاوز عشرين سنة، صحبة الفتيان في
                        المخيمات والرحلات، وفلسفة راسخة: نبني الإنسان من داخله، ثم نفتح له الأبواب.</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <span class="rounded-sm border border-white/14 bg-white/7 px-4 py-2 text-sm"><span
                                class="font-bold text-[#dfb458]">+٢٠</span> سنة خبرة تربوية</span>
                        <span class="rounded-sm border border-white/14 bg-white/7 px-4 py-2 text-sm">صحبة ميدانية
                            للفتيان</span>
                        <span class="rounded-sm border border-white/14 bg-white/7 px-4 py-2 text-sm">تحت مظلة مجموعة
                            العيسري</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="included-fees"
        class="bg-[linear-gradient(180deg,#f3f8f8,#ffffff)] py-16 lg:py-24 dark:bg-[linear-gradient(180deg,rgba(255,255,255,0.04),rgba(7,18,15,0.08))]">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="font-heading text-sm font-bold text-[#0e7c7b] dark:text-[#e0a800]">تفاصيل الرسوم</p>
                <h2 class="mt-3 font-heading text-3xl font-bold leading-tight text-[#16263f] lg:text-5xl dark:text-[#f7f1df]">
                    ما تشمله رسوم المقعد</h2>
                <p class="mt-5 text-base leading-8 text-[#566a72] dark:text-[#f7f1df]/66">خمسة عشر بندًا داخل الرسوم، مشمولة في رسم المقعد الواحد، لا يطلب عليها مبلغ إضافي في الموقع أو عند الوصول.</p>
            </div>

            <div class="mt-10 grid gap-x-10 gap-y-12 lg:grid-cols-2">
                @php($itemNumber = 0)
                @foreach ($includedFees as $group)
                    <div>
                        <div class="flex items-center gap-3">
                            <span class="flex size-11 items-center justify-center rounded-sm bg-[#0e7c7b]/10 text-[#0e7c7b] dark:bg-[#e0a800]/12 dark:text-[#e0a800]">
                                <x-hugeicon :name="$group['icon']" class="text-xl" />
                            </span>
                            <h3 class="font-heading text-2xl font-bold text-[#16263f] dark:text-[#f7f1df]">{{ $group['title'] }}</h3>
                        </div>

                        <ol class="mt-5 border-t border-[#cfe2e2] dark:border-white/10" start="{{ $itemNumber + 1 }}">
                            @foreach ($group['items'] as $item)
                                @php($itemNumber++)
                                <li class="grid grid-cols-[2.5rem_minmax(0,1fr)] gap-4 border-b border-[#cfe2e2] py-5 dark:border-white/10">
                                    <span class="flex size-9 items-center justify-center rounded-sm bg-[#16263f] font-heading text-sm font-bold text-[#dfb458] dark:bg-white/10">{{ $toArabicNumber($itemNumber) }}</span>
                                    <div>
                                        <h4 class="font-heading text-lg font-bold text-[#16263f] dark:text-[#f7f1df]">{{ $item['title'] }}</h4>
                                        <p class="mt-2 text-sm leading-7 text-[#566a72] dark:text-[#f7f1df]/66">{{ $item['body'] }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endforeach
            </div>

            <aside class="mt-12 border-s-4 border-[#dfb458] bg-[#dfb458]/10 p-6 dark:bg-[#e0a800]/10">
                <h3 class="font-heading text-2xl font-bold text-[#16263f] dark:text-[#f7f1df]">قاعدة الوضوح</h3>
                <p class="mt-3 max-w-3xl leading-8 text-[#566a72] dark:text-[#f7f1df]/72">الخمسة عشر بندًا أعلاه مشمولة بالكامل في رسم المقعد. وما لم يذكر فيها فليس مشمولًا.</p>
            </aside>
        </div>
    </section>

    <section id="seats"
        class="bg-[linear-gradient(180deg,#f3f8f8,#ffffff)] py-16 lg:py-24 dark:bg-[linear-gradient(180deg,rgba(255,255,255,0.04),rgba(7,18,15,0.08))]">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="font-heading text-sm font-bold text-[#0e7c7b] dark:text-[#e0a800]">المقاعد والتسعير</p>
                <h2
                    class="mt-3 font-heading text-3xl font-bold leading-tight text-[#16263f] lg:text-5xl dark:text-[#f7f1df]">
                    التسعير المتدرج، إنصاف للمبادر</h2>
                <p class="mt-5 text-base leading-8 text-[#566a72] dark:text-[#f7f1df]/66">المقاعد الأولى أرخص، ثم يرتفع
                    السعر شريحة بعد شريحة. من سبق سبق، وحين تنفد الشريحة لا يعود سعرها.</p>
            </div>

            @if ($openTier)
                <div
                    class="mt-10 rounded-sm bg-[linear-gradient(160deg,#0b1524,#0f1b2e)] p-5 text-[#eaf2f2] shadow-xl shadow-[#16263f]/16">
                    <div class="grid overflow-hidden rounded-sm border border-white/14 bg-white/10 md:grid-cols-3">
                        <div class="border-b border-white/10 p-5 md:border-b-0 md:border-e">
                            <p class="text-xs text-[#9dc3c3]">تبقى في الباقة المفتوحة</p>
                            <p class="mt-2 font-heading text-4xl font-bold text-[#17a3a1]">
                                {{ $toArabicNumber($openTierRemaining) }} <span
                                    class="text-base text-[#cfe2e2]">مقاعد</span></p>
                        </div>
                        <div class="border-b border-white/10 p-5 md:border-b-0 md:border-e">
                            <p class="text-xs text-[#9dc3c3]">الشريحة المفتوحة الآن</p>
                            <p class="mt-2 font-heading text-2xl font-bold text-white">{{ $openTier['name'] }}</p>
                        </div>
                        <div class="p-5">
                            <p class="text-xs text-[#9dc3c3]">سعرها الحالي</p>
                            <p class="mt-2 font-heading text-4xl font-bold text-[#dfb458]">{{ $currentPrice }} <span
                                    class="text-base text-[#cfe2e2]">ر.ع</span></p>
                        </div>
                    </div>
                    <p class="mt-4 flex items-center gap-2 text-sm text-[#a9c7c7]">
                        <x-hugeicon name="information-circle" class="text-base" />
                        <span>عداد صادق مرتبط بمصدر التسجيل عند توفره، لا رقم جامد للتسويق.</span>
                    </p>
                </div>
            @else
                <div class="mt-10 rounded-sm bg-[#0b1524] p-8 text-center text-white">
                    <p class="font-heading text-4xl font-bold">اكتملت جميع المقاعد</p>
                    <p class="mt-3 text-[#cfe2e2]">يمكنك التواصل معنا لمعرفة البرامج القادمة.</p>
                </div>
            @endif

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                @foreach ($tiers as $tier)
                    <article
                        class="relative grid overflow-hidden rounded-sm border {{ $tier['open'] ? 'border-[#0e7c7b] shadow-lg shadow-[#0e7c7b]/12' : 'border-[#d9e4e4] shadow-sm shadow-[#0f1b2e]/5' }} bg-white sm:grid-cols-[70px_minmax(0,1fr)_110px] dark:border-white/10 dark:bg-white/8">
                        @if ($tier['open'])
                            <span
                                class="absolute left-3 top-3 rounded-sm bg-[#0e7c7b] px-3 py-1 text-xs font-bold text-white">مفتوحة
                                الآن</span>
                        @else
                            <span
                                class="absolute left-3 top-3 rounded-sm bg-[#dfb458] px-3 py-1 text-xs font-bold text-[#16263f]">الباقة
                                التالية</span>
                        @endif
                        <div
                            class="flex min-h-24 flex-col items-center justify-center bg-[linear-gradient(160deg,#16263f,#0f1b2e)] text-white">
                            <span class="font-heading text-xl font-bold text-[#dfb458]">{{ $tier['code'] }}</span>
                            <span
                                class="mt-1 text-xs text-[#9dc3c3]">{{ $tier['open'] ? 'متاحة الآن' : 'تفتح لاحقًا' }}</span>
                        </div>
                        <div class="p-5">
                            <h3 class="font-heading text-xl font-bold text-[#16263f] dark:text-[#f7f1df]">
                                {{ $tier['name'] }}</h3>
                            <p class="mt-2 text-sm text-[#566a72] dark:text-[#f7f1df]/62">
                                {{ $tier['open'] ? $toArabicNumber($tier['remaining_seats']) . ' مقاعد متبقية' : 'تبدأ بعد نفاد الباقة الحالية' }}
                            </p>
                        </div>
                        <div class="flex items-center justify-start p-5 sm:justify-center">
                            <p class="font-heading text-3xl font-bold text-[#16263f] dark:text-[#f7f1df]">
                                {{ $formatPrice($tier['price_baisa']) }} <span
                                    class="text-sm text-[#566a72] dark:text-[#f7f1df]/58">ر.ع</span></p>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($availableDiscounts->isNotEmpty())
                <div class="mt-6 flex flex-wrap gap-3">
                    @foreach ($availableDiscounts as $discount)
                        <span
                            class="inline-flex items-center gap-2 rounded-sm border border-[#cfe6e5] bg-[#e6f1f1] px-4 py-3 text-sm text-[#0b5b5a] dark:border-white/10 dark:bg-white/8 dark:text-[#f7f1df]/72">
                            <x-money :amount-baisa="$discount->amount_baisa" :currency="$discount->currency"
                                class="font-bold text-[#0e7c7b] dark:text-[#e0a800]" />
                            {{ $discount->name }}
                        </span>
                    @endforeach
                </div>
            @endif

            <p class="mt-4 text-sm leading-7 text-[#566a72] dark:text-[#f7f1df]/62"> الأسعار بالريال العُماني، وتشمل
                الإقامة والإشراف ومواد البرنامج.</p>

            <div class="mt-8 flex flex-wrap gap-3">
                <x-public-event-interest-action :event="$event" button-class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm bg-[#dfb458] px-6 py-3 text-sm font-bold text-[#231703] shadow-lg shadow-[#b7892b]/20 transition hover:-translate-y-0.5 hover:bg-[#f0c96a] active:translate-y-0" />
            </div>
        </div>
    </section>

    <section id="faq"
        class="bg-[linear-gradient(180deg,#ffffff,#f3f8f8)] py-16 lg:py-24 dark:bg-[linear-gradient(180deg,rgba(255,255,255,0.04),rgba(7,18,15,0.08))]">
        <div class="mx-auto w-full max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="font-heading text-sm font-bold text-[#0e7c7b] dark:text-[#e0a800]">قبل أن تقرّر</p>
                <h2
                    class="mt-3 font-heading text-3xl font-bold leading-tight text-[#16263f] lg:text-5xl dark:text-[#f7f1df]">
                    الأسئلة الشائعة</h2>
                <p class="mt-5 text-base leading-8 text-[#566a72] dark:text-[#f7f1df]/66">جمعنا ما يشغل بال الأهل، وأجبنا
                    عنه بصراحة.</p>
            </div>

            <div class="mt-10 grid gap-3">
                @foreach ($faqs as $faq)
                    <details
                        class="group rounded-sm border border-[#d9e4e4] bg-white shadow-sm shadow-[#0f1b2e]/5 open:border-[#cfe6e5] dark:border-white/10 dark:bg-white/8">
                        <summary
                            class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 font-heading text-lg font-bold text-[#16263f] dark:text-[#f7f1df]">
                            <span>{{ $faq['question'] }}</span>
                            <x-hugeicon name="arrow-left-02"
                                class="shrink-0 text-xl text-[#0e7c7b] transition group-open:-rotate-90 dark:text-[#e0a800]" />
                        </summary>
                        <p class="px-5 pb-5 text-sm leading-8 text-[#566a72] dark:text-[#f7f1df]/66">{{ $faq['answer'] }}
                        </p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
        <div
            class="overflow-hidden rounded-sm bg-[linear-gradient(165deg,#16263f,#0f1b2e)] text-white shadow-xl shadow-[#16263f]/16">
            <div class="grid items-stretch lg:grid-cols-[minmax(0,1fr)_minmax(280px,0.36fr)]">
                <div class="p-8 lg:p-10">
                    <h2 class="font-heading text-3xl font-bold leading-tight lg:text-5xl">الطريق يبدأ من هنا، فلنمش الخطوة
                        الأولى
                        معًا</h2>
                    <p class="mt-5 max-w-2xl text-base leading-8 text-[#cfe2e2]">مقاعد الشريحة المفتوحة تنفد، وسعرها لا
                        يعود. احجز
                        لابنك اليوم، وامنحه بداية يستحقها.</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <x-public-event-interest-action :event="$event" button-class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm bg-[#dfb458] px-6 py-3 text-sm font-bold text-[#231703] shadow-lg shadow-[#b7892b]/20 transition hover:-translate-y-0.5 hover:bg-[#f0c96a] active:translate-y-0" />
                        <a href="{{ $eventUrl }}"
                            class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm border border-white/28 bg-white/6 px-6 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-white/12 active:translate-y-0">
                            <span>راجع تفاصيل الفعالية</span>
                            <x-hugeicon name="arrow-left-02" class="text-lg" />
                        </a>
                    </div>
                    <p class="mt-7 text-sm text-[#9dc3c3]">نُعِدّهم لحياة طيبة بإذن الله. حفظهم الله ورعاهم.</p>
                </div>
                <div class="aspect-[9/16] overflow-hidden bg-[#0f1b2e]">
                    <img src="{{ $finalCtaImage }}" alt="طالب عماني يبدأ الخطوة الأولى في طريقه" width="941"
                        height="1672" loading="lazy" class="h-full w-full object-cover">
                </div>
            </div>
        </div>
    </section>
@endsection
