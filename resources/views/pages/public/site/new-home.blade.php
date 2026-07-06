@php
    $eventSlug = 'your-guide-to-life-after-school';
    $eventParam = $event instanceof \App\Models\Event ? $event : $eventSlug;
    $eventUrl = route('events.show', $eventParam);
    $customerEventUrl = route('customer.events.show', $eventParam);
    $contactUrl = Route::has('contact') ? route('contact') : $eventUrl;
    $heroImage = asset('images/after-twelfth-hero.png');
    $planWorkshopImage = asset('images/after-twelfth-plan-workshop.png');
    $founderImage = asset('images/founder.webp');
    $totalCapacity = $event?->seat_capacity ?? 40;
    $remainingSeats = (int) ($remainingSeats ?? 6);
    $arabicDigits = ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩'];
    $toArabicNumber = fn (int $number): string => strtr((string) $number, $arabicDigits);

    $facts = [
        ['icon' => 'calendar-03', 'label' => '٦-٨ أغسطس ٢٠٢٦م'],
        ['icon' => 'map-pin', 'label' => 'مخيم بيرحاء، إبراء، عُمان'],
        ['icon' => 'clock-01', 'label' => '٣ أيام، وتمتد إلى ٥ عند الطلب'],
        ['icon' => 'student', 'label' => 'خرّيجو ١٢، ١٧-١٨ سنة'],
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
        ['icon' => 'student', 'title' => 'اكتشاف الذات والميول', 'body' => 'استمارة خريطة الذات: ماذا أودع الله فيك من قوّة وميل؟ نبدأ من الداخل.'],
        ['icon' => 'map-pin', 'title' => 'خرائط المسارات الأربعة', 'body' => 'الجامعي، والتقني المهني، والريادي، والتعلّم الذاتي. لا مسار واحد مقدّس.'],
        ['icon' => 'computer', 'title' => 'مهارات المستقبل', 'body' => 'ما يطلبه سوق العمل فعلًا: مهارات تتعلّم اليوم، بلا انتظار جامعة.'],
        ['icon' => 'checkmark-badge-01', 'title' => 'الدعم النفسي وإعادة تعريف الفشل', 'body' => 'قصص ناجحين بمسارات غير تقليدية، وجلسات تفريغ ومصارحة تطمئن القلب.'],
        ['icon' => 'file-view', 'title' => 'خطة التسعين يومًا', 'body' => 'يخرج كل خرّيج بخطة مكتوبة بخط يده: قرار عملي يبدأ به من الغد.'],
    ];

    $tiers = [
        ['code' => 'B1', 'name' => 'الباكورة', 'seats' => 10, 'price' => 45, 'note' => 'المبادر أولًا', 'open' => true],
        ['code' => 'B2', 'name' => 'المتقدمة', 'seats' => 10, 'price' => 55, 'note' => 'قبل امتلاء المقاعد', 'open' => false],
        ['code' => 'B3', 'name' => 'القياسية', 'seats' => 8, 'price' => 65, 'note' => 'الشريحة الوسطى', 'open' => false],
        ['code' => 'B4', 'name' => 'الختامية', 'seats' => 4, 'price' => 75, 'note' => 'آخر المقاعد', 'open' => false],
    ];

    $faqs = [
        ['question' => 'أين تُقام الفعالية ومتى؟', 'answer' => 'تُقام في مخيم بيرحاء بولاية إبراء، سلطنة عُمان، أيام ٦-٨ أغسطس ٢٠٢٦م. ثلاثة أيام إقامية مركزة، وتمتد إلى خمسة عند الطلب.'],
        ['question' => 'كيف يُشرَف على الأبناء ليلًا ونهارًا؟', 'answer' => 'الإشراف كامل على مدار الوقت، بمشرفين مؤهلين للمجموعات الصغيرة، داخل بيئة آمنة بقيم تربوية تعرفها الأسرة.'],
        ['question' => 'كيف نتواصل مع ابننا أثناء الأيام الثلاثة؟', 'answer' => 'تصلكم تقارير متابعة تطمئنكم أولًا بأول عبر قناة تواصل مخصصة، مع إتاحة التواصل المباشر في الأوقات المناسبة دون تشتيت البرنامج.'],
        ['question' => 'بماذا يعود ابني فعليًا من هذه الأيام؟', 'answer' => 'يعود بخريطة مسار واضحة، وبخطة ٩٠ يومًا مكتوبة بخط يده، وبثقة مستعادة في نفسه ومستقبله.'],
        ['question' => 'ما الفرق بين هذه الفعالية ودورات التوجيه المهني التقليدية؟', 'answer' => 'الدورات تلقّن معلومات، أما هذه الفعالية فتعيش تجربة. لا نبدأ من قائمة التخصصات، بل من ميل ابنك وقيمته وثقته.'],
        ['question' => 'ما معنى التسعير المتدرج؟', 'answer' => 'نطرح المقاعد على شرائح تصعد كلما اقترب الموعد ونفدت المقاعد. من بادر نال أرخصها، وحين تنفد الشريحة لا يعود سعرها.'],
        ['question' => 'ما مقاعد الرحمة؟ ومن يستحقها؟', 'answer' => 'مقاعد مدعومة لمن حالت ظروفه المادية دون الرسوم. إن كان ابنك من أهلها فكلّمنا، والأمر بيننا وبينكم.'],
        ['question' => 'هل الفعالية للفتيان فقط؟ وما الفئة العمرية؟', 'answer' => 'نعم، هذه الفعالية الإقامية مخصصة لخرّيجي الثاني عشر من الفتيان، ١٧-١٨ سنة، لطبيعة الإقامة الكاملة والإشراف.'],
    ];
@endphp

@extends('layouts.public', [
    'title' => 'بعد الثاني عشر، الطريق يبدأ',
    'metaDescription' => 'فعالية إقامية لخرّيجي الثاني عشر في مخيم بيرحاء بإبراء، يغادر فيها المشارك بخطة ٩٠ يومًا مكتوبة بيده.',
])

@section('content')
    <section class="relative isolate overflow-hidden bg-[#0b1524] text-white">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_85%_0%,rgba(23,163,161,0.26),transparent_36%),radial-gradient(circle_at_4%_6%,rgba(223,180,88,0.22),transparent_34%),linear-gradient(180deg,#0b1524,#0f1b2e_58%,#16263f)]"></div>
        <div class="absolute inset-x-0 bottom-0 h-1 bg-[linear-gradient(90deg,transparent,#b7892b_18%,#dfb458_50%,#b7892b_82%,transparent)]"></div>

        <div class="mx-auto grid min-h-[calc(100dvh-5rem)] w-full max-w-7xl items-center gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,1fr)_minmax(340px,0.72fr)] lg:px-8 lg:py-18">
            <div class="max-w-4xl">
                <div class="flex flex-wrap items-center gap-3 text-sm text-[#9dc3c3]">
                    <span class="font-heading text-xl font-bold text-white">بِيرُحاء</span>
                    <span>للسياحة والتجارة</span>
                    <span class="h-1.5 w-1.5 rounded-full bg-[#dfb458]"></span>
                    <span>مجموعة العيسري</span>
                </div>

                <p class="mt-7 inline-flex min-h-10 items-center gap-2 rounded-sm border border-[#17a3a1]/34 bg-[#17a3a1]/14 px-4 py-2 text-sm font-bold text-[#bee9e8]">
                    <x-hugeicon name="student" class="text-lg" />
                    <span>فعالية إقامية لخرّيجي الثاني عشر، للفتيان</span>
                </p>

                <h1 class="mt-6 max-w-4xl font-heading text-4xl font-bold leading-tight sm:text-5xl lg:text-6xl">
                    بعد الثاني عشر، <span class="text-[#dfb458]">الطريق يبدأ من هنا</span>
                </h1>

                <p class="mt-6 max-w-2xl text-lg leading-9 text-[#cfe2e2]">
                    النجاح أوسع من معدّل، والطريق أرحب من خيار واحد. ثلاثة أيام تعيد لابنك يقينه، وتفتح له الأبواب.
                </p>

                <div class="mt-7 flex max-w-2xl gap-4 rounded-sm border border-white/12 bg-white/6 p-5">
                    <x-hugeicon name="file-view" class="mt-1 text-2xl text-[#dfb458]" />
                    <p class="text-base leading-8 text-[#cfe2e2]">
                        <span class="font-bold text-white">الوعد الملموس:</span>
                        يغادر ابنك بخطة ٩٠ يومًا مكتوبة بيده، قرار عملي لا كلام عام، حفظه الله.
                    </p>
                </div>

                <div class="mt-7 flex flex-wrap gap-2">
                    @foreach ($facts as $fact)
                        <span class="inline-flex min-h-10 items-center gap-2 rounded-sm border border-white/12 bg-white/6 px-3 py-2 text-sm text-[#dcebeb]">
                            <x-hugeicon :name="$fact['icon']" class="text-base text-[#9dc3c3]" />
                            <span>{{ $fact['label'] }}</span>
                        </span>
                    @endforeach
                </div>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="#seats" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm bg-[#dfb458] px-6 py-3 text-sm font-bold text-[#231703] shadow-lg shadow-[#b7892b]/30 transition hover:-translate-y-0.5 hover:bg-[#f0c96a] active:translate-y-0">
                        <span>احجز مقعد ابنك الآن</span>
                        <x-hugeicon name="arrow-left-02" class="text-lg" />
                    </a>

                    <a href="#program" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm border border-white/28 bg-white/6 px-6 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-white/12 active:translate-y-0">
                        <span>ماذا يحدث في الأيام الثلاثة؟</span>
                        <x-hugeicon name="check-list" class="text-lg" />
                    </a>
                </div>
            </div>

            <aside class="overflow-hidden rounded-sm border border-white/12 bg-white/7 shadow-2xl shadow-black/24">
                <div class="relative aspect-[4/5] overflow-hidden sm:aspect-[5/4] lg:aspect-[4/5]">
                    <img src="{{ $heroImage }}" alt="طلاب عمانيون مع مرشد في مخيم جبلي عند الشروق" width="1792" height="1024" fetchpriority="high" class="h-full w-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#0b1524]/82 via-[#0b1524]/8 to-transparent"></div>
                    <div class="absolute inset-x-5 bottom-5 rounded-sm border border-white/12 bg-[#0b1524]/58 p-4 text-white backdrop-blur-md">
                        <p class="text-sm text-[#cfe2e2]">تجربة إقامية داخل عُمان</p>
                        <p class="mt-1 font-heading text-2xl font-bold">مخيم بيرحاء، إبراء</p>
                    </div>
                </div>

                <div class="p-5">
                <div class="rounded-sm border border-[#dfb458]/24 bg-[#dfb458]/10 p-5">
                    <p class="text-sm text-[#f4dfb2]">الشريحة المفتوحة الآن</p>
                    <p class="mt-2 font-heading text-4xl font-bold text-white">الباكورة</p>
                    <p class="mt-3 text-sm leading-7 text-[#cfe2e2]">المقاعد الأولى أرخص، وحين تنفد الشريحة لا يعود سعرها.</p>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-3">
                    <div class="rounded-sm bg-white/8 p-4 text-center">
                        <p class="text-xs text-[#9dc3c3]">المتبقي</p>
                        <p class="mt-2 font-heading text-3xl font-bold text-[#17a3a1]">{{ $toArabicNumber($remainingSeats) }}</p>
                    </div>
                    <div class="rounded-sm bg-white/8 p-4 text-center">
                        <p class="text-xs text-[#9dc3c3]">السعر</p>
                        <p class="mt-2 font-heading text-3xl font-bold text-[#dfb458]">٤٥</p>
                    </div>
                    <div class="rounded-sm bg-white/8 p-4 text-center">
                        <p class="text-xs text-[#9dc3c3]">السعة</p>
                        <p class="mt-2 font-heading text-3xl font-bold">{{ $toArabicNumber($totalCapacity) }}</p>
                    </div>
                </div>
                </div>
            </aside>
        </div>
    </section>

    <section id="worries" class="bg-[linear-gradient(180deg,#ffffff,#f3f8f8)] py-16 lg:py-24 dark:bg-[linear-gradient(180deg,rgba(255,255,255,0.04),rgba(7,18,15,0.08))]">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="font-heading text-sm font-bold text-[#0e7c7b] dark:text-[#e0a800]">نفهم قلقك</p>
                <h2 class="mt-3 font-heading text-3xl font-bold leading-tight text-[#16263f] lg:text-5xl dark:text-[#f7f1df]">أربعة هموم تسكن الخرّيج وأهله، ونفتح لكل منها مخرجًا</h2>
                <p class="mt-5 text-base leading-8 text-[#566a72] dark:text-[#f7f1df]/66">لا نهوّن من الأمر، ولا نضخّمه. نضعه على الطاولة، ونمشي منه إلى حل عملي.</p>
            </div>

            <div class="mt-10 grid gap-5 md:grid-cols-2">
                @foreach ($worries as $worry)
                    <article class="rounded-sm border border-[#d9e4e4] bg-white p-6 shadow-sm shadow-[#0f1b2e]/5 dark:border-white/10 dark:bg-white/8 dark:shadow-black/20">
                        <div class="flex items-start gap-4">
                            <span class="flex size-11 shrink-0 items-center justify-center rounded-sm bg-[#e6f1f1] text-[#0e7c7b] dark:bg-[#e0a800]/12 dark:text-[#e0a800]">
                                <x-hugeicon :name="$worry['icon']" class="text-xl" />
                            </span>
                            <div>
                                <h3 class="font-heading text-xl font-bold text-[#16263f] dark:text-[#f7f1df]">{{ $worry['title'] }}</h3>
                                <p class="mt-3 text-sm leading-7 text-[#566a72] dark:text-[#f7f1df]/64">{{ $worry['body'] }}</p>
                            </div>
                        </div>
                        <div class="mt-5 border-t border-dashed border-[#d9e4e4] pt-4 text-sm leading-7 text-[#152230] dark:border-white/10 dark:text-[#f7f1df]/78">
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
            <h2 class="mt-3 font-heading text-3xl font-bold leading-tight text-[#16263f] lg:text-5xl dark:text-[#f7f1df]">نخاطب الأب والابن، كل بلغته</h2>
            <p class="mt-5 text-base leading-8 text-[#566a72] dark:text-[#f7f1df]/66">قرار الأب ثقة، ويقين الابن استعادة. وكلاهما هدفنا في هذه الأيام.</p>
        </div>

        <div class="mt-10 grid gap-5 lg:grid-cols-2">
            <article class="rounded-sm bg-[linear-gradient(165deg,#16263f,#0f1b2e)] p-7 text-[#eaf2f2] shadow-xl shadow-[#16263f]/16">
                <p class="inline-flex items-center gap-2 rounded-sm border border-white/20 bg-white/12 px-3 py-1.5 text-sm font-bold">
                    <x-hugeicon name="user-group" class="text-lg" />
                    بعيني ولي الأمر
                </p>
                <h3 class="mt-5 font-heading text-2xl font-bold text-white">ترى ابنك يخرج بخطة وأمل، لا بإحباط ومقارنة</h3>
                <p class="mt-4 leading-8 text-[#cfe2e2]">تسلّمه إلينا قلقًا حائرًا، فيعود إليك، حفظه الله، واثقًا يحمل بيده خريطة مسار وقرارًا مكتوبًا.</p>
                <p class="mt-4 leading-8 text-[#cfe2e2]"><span class="font-bold text-[#dfb458]">إشراف كامل، وتقارير تطمئنك</span> على ابنك أولًا بأول، في بيئة آمنة بقيم تعرفها.</p>
            </article>

            <article class="rounded-sm bg-[linear-gradient(165deg,#0e7c7b,#0b6968)] p-7 text-[#eaf7f6] shadow-xl shadow-[#0e7c7b]/16">
                <p class="inline-flex items-center gap-2 rounded-sm border border-white/20 bg-white/12 px-3 py-1.5 text-sm font-bold">
                    <x-hugeicon name="student" class="text-lg" />
                    بعيني الخرّيج
                </p>
                <h3 class="mt-5 font-heading text-2xl font-bold text-white">لست متأخرًا، ولست وحدك. الطريق أمامك أوسع مما تظن</h3>
                <p class="mt-4 leading-8 text-[#eaf7f6]/90">ستكتشف ما أودعه الله فيك من قوة وميل، وتلتقي من مشى الطريق قبلك بمسار غير تقليدي.</p>
                <p class="mt-4 leading-8 text-[#eaf7f6]/90"><span class="font-bold text-[#dfb458]">تغادر بخطة بخط يدك</span>، أول خطوة نحو حياة تختارها أنت، لا حياة تنتظرها.</p>
            </article>
        </div>
    </section>

    <section id="program" class="bg-[linear-gradient(180deg,#ffffff,#f3f8f8)] py-16 lg:py-24 dark:bg-[linear-gradient(180deg,rgba(255,255,255,0.04),rgba(7,18,15,0.08))]">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="font-heading text-sm font-bold text-[#0e7c7b] dark:text-[#e0a800]">محاور البرنامج</p>
                <h2 class="mt-3 font-heading text-3xl font-bold leading-tight text-[#16263f] lg:text-5xl dark:text-[#f7f1df]">خمس محطات في ثلاثة أيام</h2>
                <p class="mt-5 text-base leading-8 text-[#566a72] dark:text-[#f7f1df]/66">كل محطة تنقله خطوة: من اكتشاف ذاته، إلى قرار مكتوب يبدأ به.</p>
            </div>

            <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <article class="overflow-hidden rounded-sm border border-[#d9e4e4] bg-white shadow-sm shadow-[#0f1b2e]/5 sm:col-span-2 dark:border-white/10 dark:bg-white/8 dark:shadow-black/20">
                    <div class="aspect-[16/10] overflow-hidden">
                        <img src="{{ $planWorkshopImage }}" alt="تخطيط عملي لخطة تسعين يومًا في مجلس تعليمي خارجي" width="1536" height="1024" loading="lazy" class="h-full w-full object-cover">
                    </div>
                    <div class="p-6">
                        <p class="font-heading text-sm font-bold text-[#0e7c7b] dark:text-[#e0a800]">المخرج العملي</p>
                        <h3 class="mt-3 font-heading text-2xl font-bold text-[#16263f] dark:text-[#f7f1df]">خطة مكتوبة، لا محاضرة عابرة</h3>
                        <p class="mt-3 text-sm leading-7 text-[#566a72] dark:text-[#f7f1df]/64">نحوّل الحوار إلى أوراق عمل وخطوات يكتبها المشارك بيده، حتى يعود إلى البيت ومعه بداية واضحة.</p>
                    </div>
                </article>

                @foreach ($stations as $station)
                    <article class="rounded-sm border border-[#d9e4e4] bg-white p-6 shadow-sm shadow-[#0f1b2e]/5 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-[#0f1b2e]/8 dark:border-white/10 dark:bg-white/8 dark:shadow-black/20">
                        <p class="font-heading text-sm font-bold text-[#b7892b]">المحطة {{ $toArabicNumber($loop->iteration) }}</p>
                        <span class="mt-4 flex size-13 items-center justify-center rounded-sm bg-[linear-gradient(160deg,#17a3a1,#0e7c7b)] text-white">
                            <x-hugeicon :name="$station['icon']" class="text-2xl" />
                        </span>
                        <h3 class="mt-5 font-heading text-xl font-bold text-[#16263f] dark:text-[#f7f1df]">{{ $station['title'] }}</h3>
                        <p class="mt-3 text-sm leading-7 text-[#566a72] dark:text-[#f7f1df]/64">{{ $station['body'] }}</p>
                    </article>
                @endforeach

                <article class="grid place-content-center rounded-sm bg-[linear-gradient(160deg,#16263f,#0f1b2e)] p-6 text-center text-[#eaf2f2] shadow-lg shadow-[#16263f]/16">
                    <p class="font-heading text-xl font-bold text-[#dfb458]">من المحطة الأولى إلى القرار</p>
                    <p class="mt-3 leading-7 text-[#cfe2e2]">ثلاثة أيام مركزة، تمتد إلى خمس عند الطلب.</p>
                </article>
            </div>
        </div>
    </section>

    <section id="leader" class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
        <div class="max-w-3xl">
            <p class="font-heading text-sm font-bold text-[#0e7c7b] dark:text-[#e0a800]">مَن يقود؟</p>
            <h2 class="mt-3 font-heading text-3xl font-bold leading-tight text-[#16263f] lg:text-5xl dark:text-[#f7f1df]">الثقة تسبق القرار</h2>
            <p class="mt-5 text-base leading-8 text-[#566a72] dark:text-[#f7f1df]/66">قبل أن تحجز، تعرف من يستقبل ابنك، وبأي خبرة وفلسفة.</p>
        </div>

        <div class="mt-10 overflow-hidden rounded-sm bg-[linear-gradient(165deg,#16263f,#0f1b2e)] text-[#eaf2f2] shadow-xl shadow-[#16263f]/16">
            <div class="grid items-stretch gap-0 md:grid-cols-[minmax(260px,0.42fr)_minmax(0,0.58fr)]">
                <div class="relative min-h-80 overflow-hidden">
                    <img src="{{ $founderImage }}" alt="أبو بلج، عبدالله العيسري" width="1024" height="1024" loading="lazy" class="h-full w-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-t from-[#0f1b2e]/62 via-transparent to-transparent"></div>
                </div>
                <div class="p-7 lg:p-9">
                    <p class="font-heading text-sm font-bold text-[#dfb458]">قيادة الفعالية</p>
                    <h3 class="mt-2 font-heading text-3xl font-bold text-white">أبو بلج، عبدالله العيسري</h3>
                    <p class="mt-2 text-[#9dc3c3]">مؤسس بيرحاء ومرجعها التربوي، مجموعة العيسري</p>
                    <p class="mt-5 max-w-3xl leading-8 text-[#cfe2e2]">خبرة تربوية تتجاوز عشرين سنة، صحبة الفتيان في المخيمات والرحلات، وفلسفة راسخة: نبني الإنسان من داخله، ثم نفتح له الأبواب.</p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <span class="rounded-sm border border-white/14 bg-white/7 px-4 py-2 text-sm"><span class="font-bold text-[#dfb458]">+٢٠</span> سنة خبرة تربوية</span>
                        <span class="rounded-sm border border-white/14 bg-white/7 px-4 py-2 text-sm">صحبة ميدانية للفتيان</span>
                        <span class="rounded-sm border border-white/14 bg-white/7 px-4 py-2 text-sm">تحت مظلة مجموعة العيسري</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="mercy" class="bg-[linear-gradient(180deg,#f3f8f8,#ffffff)] py-16 lg:py-24 dark:bg-[linear-gradient(180deg,rgba(7,18,15,0.08),rgba(255,255,255,0.04))]">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid items-center gap-6 rounded-sm border border-[#ead9ae] bg-[linear-gradient(165deg,#f7efdc,#ffffff)] p-7 shadow-sm shadow-[#0f1b2e]/5 md:grid-cols-[96px_minmax(0,1fr)] dark:border-[#e0a800]/20 dark:bg-[linear-gradient(165deg,rgba(224,168,0,0.12),rgba(255,255,255,0.06))]">
                <div class="mx-auto flex size-24 items-center justify-center rounded-sm bg-[linear-gradient(160deg,#dfb458,#b7892b)] text-white">
                    <x-hugeicon name="checkmark-circle-01" class="text-4xl" />
                </div>
                <div>
                    <h2 class="font-heading text-3xl font-bold text-[#16263f] dark:text-[#f7f1df]">مقاعد الرحمة</h2>
                    <p class="mt-4 leading-8 text-[#5a4a28] dark:text-[#f7f1df]/72">من كل أربعين مقعدًا، خصصنا ٨ مقاعد رحمة مدعومة لمن حالت ظروفه دون الرسوم.</p>
                    <p class="mt-3 leading-8 text-[#5a4a28] dark:text-[#f7f1df]/72">البرنامج صُمم ليحوّل من يحتاج التغيير، لا ليصطفي من يقدر على الدفع. إن كان ابنك من أهلها، فكلّمنا، والأمر بيننا وبينكم.</p>
                    <a href="{{ $contactUrl }}" class="mt-5 inline-flex min-h-11 items-center justify-center gap-2 rounded-sm bg-[#0e7c7b] px-5 py-2.5 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-[#0b6968] active:translate-y-0 dark:bg-[#e0a800] dark:text-[#07120f]">
                        <span>استفسر عن مقعد الرحمة</span>
                        <x-hugeicon name="mail-01" class="text-lg" />
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section id="seats" class="bg-[linear-gradient(180deg,#f3f8f8,#ffffff)] py-16 lg:py-24 dark:bg-[linear-gradient(180deg,rgba(255,255,255,0.04),rgba(7,18,15,0.08))]">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="font-heading text-sm font-bold text-[#0e7c7b] dark:text-[#e0a800]">المقاعد والتسعير</p>
                <h2 class="mt-3 font-heading text-3xl font-bold leading-tight text-[#16263f] lg:text-5xl dark:text-[#f7f1df]">التسعير المتدرج، إنصاف للمبادر</h2>
                <p class="mt-5 text-base leading-8 text-[#566a72] dark:text-[#f7f1df]/66">المقاعد الأولى أرخص، ثم يرتفع السعر شريحة بعد شريحة. من سبق سبق، وحين تنفد الشريحة لا يعود سعرها.</p>
            </div>

            <div class="mt-10 rounded-sm bg-[linear-gradient(160deg,#0b1524,#0f1b2e)] p-5 text-[#eaf2f2] shadow-xl shadow-[#16263f]/16">
                <div class="grid overflow-hidden rounded-sm border border-white/14 bg-white/10 md:grid-cols-3">
                    <div class="border-b border-white/10 p-5 md:border-b-0 md:border-e">
                        <p class="text-xs text-[#9dc3c3]">تبقى في الشريحة المفتوحة</p>
                        <p class="mt-2 font-heading text-4xl font-bold text-[#17a3a1]">{{ $toArabicNumber($remainingSeats) }} <span class="text-base text-[#cfe2e2]">مقاعد</span></p>
                    </div>
                    <div class="border-b border-white/10 p-5 md:border-b-0 md:border-e">
                        <p class="text-xs text-[#9dc3c3]">الشريحة المفتوحة الآن</p>
                        <p class="mt-2 font-heading text-2xl font-bold text-white">الباكورة</p>
                    </div>
                    <div class="p-5">
                        <p class="text-xs text-[#9dc3c3]">سعرها الحالي</p>
                        <p class="mt-2 font-heading text-4xl font-bold text-[#dfb458]">٤٥ <span class="text-base text-[#cfe2e2]">ر.ع</span></p>
                    </div>
                </div>
                <p class="mt-4 flex items-center gap-2 text-sm text-[#a9c7c7]">
                    <x-hugeicon name="information-circle" class="text-base" />
                    <span>عداد صادق مرتبط بمصدر التسجيل عند توفره، لا رقم جامد للتسويق.</span>
                </p>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2">
                @foreach ($tiers as $tier)
                    <article class="relative grid overflow-hidden rounded-sm border {{ $tier['open'] ? 'border-[#0e7c7b] shadow-lg shadow-[#0e7c7b]/12' : 'border-[#d9e4e4] shadow-sm shadow-[#0f1b2e]/5' }} bg-white sm:grid-cols-[70px_minmax(0,1fr)_110px] dark:border-white/10 dark:bg-white/8">
                        @if ($tier['open'])
                            <span class="absolute left-3 top-3 rounded-sm bg-[#0e7c7b] px-3 py-1 text-xs font-bold text-white">مفتوحة الآن</span>
                        @endif
                        <div class="flex min-h-24 flex-col items-center justify-center bg-[linear-gradient(160deg,#16263f,#0f1b2e)] text-white">
                            <span class="font-heading text-xl font-bold text-[#dfb458]">{{ $tier['code'] }}</span>
                            <span class="mt-1 text-xs text-[#9dc3c3]">{{ $toArabicNumber($tier['seats']) }} مقاعد</span>
                        </div>
                        <div class="p-5">
                            <h3 class="font-heading text-xl font-bold text-[#16263f] dark:text-[#f7f1df]">{{ $tier['name'] }}</h3>
                            <p class="mt-2 text-sm text-[#566a72] dark:text-[#f7f1df]/62">{{ $tier['note'] }}</p>
                        </div>
                        <div class="flex items-center justify-start p-5 sm:justify-center">
                            <p class="font-heading text-3xl font-bold text-[#16263f] dark:text-[#f7f1df]">{{ $toArabicNumber($tier['price']) }} <span class="text-sm text-[#566a72] dark:text-[#f7f1df]/58">ر.ع</span></p>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-6 flex flex-wrap gap-3">
                <span class="inline-flex items-center gap-2 rounded-sm border border-[#cfe6e5] bg-[#e6f1f1] px-4 py-3 text-sm text-[#0b5b5a] dark:border-white/10 dark:bg-white/8 dark:text-[#f7f1df]/72"><span class="font-bold text-[#0e7c7b] dark:text-[#e0a800]">-٥٪</span> خصم الإخوة</span>
                <span class="inline-flex items-center gap-2 rounded-sm border border-[#cfe6e5] bg-[#e6f1f1] px-4 py-3 text-sm text-[#0b5b5a] dark:border-white/10 dark:bg-white/8 dark:text-[#f7f1df]/72"><span class="font-bold text-[#0e7c7b] dark:text-[#e0a800]">-٨٪</span> خصم المجموعة، ٣ فأكثر</span>
            </div>

            <p class="mt-4 text-sm leading-7 text-[#566a72] dark:text-[#f7f1df]/62">المقاعد الكلية {{ $toArabicNumber($totalCapacity) }} مقعدًا، منها ٨ مقاعد رحمة مدعومة. الأسعار بالريال العُماني، وتشمل الإقامة والإشراف ومواد البرنامج.</p>

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ $customerEventUrl }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm bg-[#dfb458] px-6 py-3 text-sm font-bold text-[#231703] shadow-lg shadow-[#b7892b]/20 transition hover:-translate-y-0.5 hover:bg-[#f0c96a] active:translate-y-0">
                    <span>احجز الآن، الشريحة المفتوحة</span>
                    <x-hugeicon name="check-list" class="text-lg" />
                </a>
                <a href="{{ $contactUrl }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm bg-[#1fa855] px-6 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-[#1a8f48] active:translate-y-0">
                    <span>استفسر قبل الحجز</span>
                    <x-hugeicon name="mail-01" class="text-lg" />
                </a>
            </div>
        </div>
    </section>

    <section id="proof" class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
        <div class="max-w-3xl">
            <p class="font-heading text-sm font-bold text-[#0e7c7b] dark:text-[#e0a800]">أصوات الأهل</p>
            <h2 class="mt-3 font-heading text-3xl font-bold leading-tight text-[#16263f] lg:text-5xl dark:text-[#f7f1df]">شهادات حقيقية، قريبًا</h2>
            <p class="mt-5 text-base leading-8 text-[#566a72] dark:text-[#f7f1df]/66">لا نضع كلامًا لم يُقَل. حين تنطق تجارب الأهل، نضعها هنا كما هي.</p>
        </div>
        <div class="mt-10 rounded-sm border-2 border-dashed border-[#d9e4e4] bg-white p-8 text-center text-[#566a72] dark:border-white/10 dark:bg-white/8 dark:text-[#f7f1df]/66">
            <p class="text-[#dfb458]">★ ★ ★ ★ ★</p>
            <p class="mt-4 font-bold text-[#16263f] dark:text-[#f7f1df]">موضع معد لشهادات أولياء الأمور والتقييم النجمي.</p>
            <p class="mt-2">البنية جاهزة للتفعيل فور توفر التقييمات الحقيقية.</p>
        </div>
    </section>

    <section id="faq" class="bg-[linear-gradient(180deg,#ffffff,#f3f8f8)] py-16 lg:py-24 dark:bg-[linear-gradient(180deg,rgba(255,255,255,0.04),rgba(7,18,15,0.08))]">
        <div class="mx-auto w-full max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="font-heading text-sm font-bold text-[#0e7c7b] dark:text-[#e0a800]">قبل أن تقرّر</p>
                <h2 class="mt-3 font-heading text-3xl font-bold leading-tight text-[#16263f] lg:text-5xl dark:text-[#f7f1df]">الأسئلة الشائعة</h2>
                <p class="mt-5 text-base leading-8 text-[#566a72] dark:text-[#f7f1df]/66">جمعنا ما يشغل بال الأهل، وأجبنا عنه بصراحة.</p>
            </div>

            <div class="mt-10 grid gap-3">
                @foreach ($faqs as $faq)
                    <details class="group rounded-sm border border-[#d9e4e4] bg-white shadow-sm shadow-[#0f1b2e]/5 open:border-[#cfe6e5] dark:border-white/10 dark:bg-white/8">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 font-heading text-lg font-bold text-[#16263f] dark:text-[#f7f1df]">
                            <span>{{ $faq['question'] }}</span>
                            <x-hugeicon name="arrow-left-02" class="shrink-0 text-xl text-[#0e7c7b] transition group-open:-rotate-90 dark:text-[#e0a800]" />
                        </summary>
                        <p class="px-5 pb-5 text-sm leading-8 text-[#566a72] dark:text-[#f7f1df]/66">{{ $faq['answer'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
        <div class="rounded-sm bg-[linear-gradient(165deg,#16263f,#0f1b2e)] p-8 text-white shadow-xl shadow-[#16263f]/16 lg:p-10">
            <h2 class="font-heading text-3xl font-bold leading-tight lg:text-5xl">الطريق يبدأ من هنا، فلنمش الخطوة الأولى معًا</h2>
            <p class="mt-5 max-w-2xl text-base leading-8 text-[#cfe2e2]">مقاعد الشريحة المفتوحة تنفد، وسعرها لا يعود. احجز لابنك اليوم، وامنحه بداية يستحقها.</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ $customerEventUrl }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm bg-[#dfb458] px-6 py-3 text-sm font-bold text-[#231703] shadow-lg shadow-[#b7892b]/20 transition hover:-translate-y-0.5 hover:bg-[#f0c96a] active:translate-y-0">
                    <span>احجز مقعد ابنك الآن</span>
                    <x-hugeicon name="check-list" class="text-lg" />
                </a>
                <a href="{{ $eventUrl }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm border border-white/28 bg-white/6 px-6 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-white/12 active:translate-y-0">
                    <span>راجع تفاصيل الفعالية</span>
                    <x-hugeicon name="arrow-left-02" class="text-lg" />
                </a>
            </div>
            <p class="mt-7 text-sm text-[#9dc3c3]">نُعِدّهم لحياة طيبة بإذن الله. حفظهم الله ورعاهم.</p>
        </div>
    </section>
@endsection
