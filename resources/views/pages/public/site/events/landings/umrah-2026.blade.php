@php
    $heroImage = asset('images/umrah-2026-hero.png');
    $preparationImage = asset('images/after-twelfth-mentor-circle.png');

    $phases = [
        [
            'date' => '٢٠ إلى ٢٢ أغسطس',
            'title' => 'تهيئة في بيرحاء',
            'body' => 'تعارف مع المجموعة، وتعلّم عملي للمناسك، وترتيب للأوراق والحقائب قبل الطريق.',
            'icon' => 'book-open-text',
            'class' => 'md:col-span-7',
        ],
        [
            'date' => '٢٣ إلى ٢٤ أغسطس',
            'title' => 'الطريق والوصول',
            'body' => 'رحلة برية منظمة بمحطات راحة وصلاة، ثم استقرار في السكن قبل أداء النسك.',
            'icon' => 'map-pin',
            'class' => 'md:col-span-5',
        ],
        [
            'date' => '٢٥ إلى ٢٧ أغسطس',
            'title' => 'أيام مكة',
            'body' => 'عمرة وصلوات وحلقات قصيرة ووقت متوازن للعبادة والراحة والتواصل مع الأسرة.',
            'icon' => 'moon-02',
            'class' => 'md:col-span-5',
        ],
        [
            'date' => '٢٨ إلى ٢٩ أغسطس',
            'title' => 'عودة بثمرة مكتوبة',
            'body' => 'طريق العودة، وتقويم للتجربة، ولقاء ختامي في مخيم بيرحاء قبل التسليم لولي الأمر.',
            'icon' => 'checkmark-badge-01',
            'class' => 'md:col-span-7',
        ],
    ];

    $safetyPoints = [
        ['icon' => 'user-group', 'title' => 'مشرف ثابت', 'body' => 'يعرف الفتى مجموعته ومشرفه منذ أيام المخيم وحتى العودة.'],
        ['icon' => 'mail-01', 'title' => 'تواصل يومي', 'body' => 'وقت واضح للاطمئنان، وقناة مباشرة لولي الأمر عند الحاجة.'],
        ['icon' => 'file-view', 'title' => 'معلومات صحية', 'body' => 'تُجمع التعليمات الصحية والأدوية قبل السفر وتبقى مع الفريق المسؤول.'],
        ['icon' => 'map-pin', 'title' => 'طريق منظم', 'body' => 'محطات راحة وصلاة، ومواعيد تفصيلية تُعتمد قبل الانطلاق.'],
    ];

    $faqs = [
        [
            'question' => 'لمن هذه الرحلة؟',
            'answer' => 'للفتيان من ١٦ إلى ١٨ سنة، وخصوصًا طلاب الصفوف العاشر والحادي عشر والثاني عشر.',
        ],
        [
            'question' => 'لماذا تبدأ الرحلة بثلاثة أيام في المخيم؟',
            'answer' => 'حتى يتعلم الفتى المناسك عمليًا، ويتعرف على مجموعته ومشرفه، ويبدأ الطريق وهو مستعد لا مرتبك.',
        ],
        [
            'question' => 'ما الوثائق المطلوبة؟',
            'answer' => 'جواز سفر ساري المفعول، وموافقة خطية من ولي الأمر، ومعلومات صحية مكتملة. ترسل القائمة النهائية بعد الحجز.',
        ],
        [
            'question' => 'كيف يتم الحجز والدفع؟',
            'answer' => 'يبدأ الحجز من حساب ولي الأمر في الموقع. يظهر السعر وخيارات السداد وأي خصم معتمد قبل إرسال الطلب.',
        ],
        [
            'question' => 'هل مواعيد الطريق نهائية؟',
            'answer' => 'التواريخ معتمدة، أما ساعات التجمع والوصول فتُسلّم لاحقًا لأنها تتأثر بترتيبات الطريق وإجراءات المنافذ.',
        ],
    ];
@endphp

@extends('layouts.public', [
    'title' => $title,
    'metaDescription' => $metaDescription,
    'metaImage' => $heroImage,
])

@section('content')
    <article>
        <section class="relative isolate overflow-hidden bg-[#0b211d] text-white">
            <div
                class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_82%_12%,rgba(26,143,112,0.32),transparent_34%),radial-gradient(circle_at_10%_82%,rgba(183,137,43,0.14),transparent_34%)]">
            </div>

            <div
                class="mx-auto grid min-h-[calc(100dvh-5rem)] w-full max-w-7xl items-center gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(480px,1.1fr)] lg:px-8 lg:py-16">
                <div class="public-hero-copy max-w-3xl">
                    <a href="{{ route('events.index') }}"
                        class="inline-flex min-h-10 items-center gap-2 rounded-sm border border-white/16 bg-white/6 px-3 py-2 text-sm font-bold text-[#b9ead9] transition hover:bg-white/10">
                        <x-hugeicon name="calendar-03" class="text-lg" />
                        برنامج بيرحاء الخارجي
                    </a>

                    <h1 class="mt-5 font-heading text-4xl font-bold leading-[1.2] sm:text-5xl lg:text-6xl">
                        رحلة العمرة بصحبة أبي بلج
                    </h1>

                    <p class="mt-6 max-w-[58ch] text-lg leading-8 text-white/76">
                        عشرة أيام تبدأ بثلاثة في مخيم بيرحاء، ثم طريق منظم إلى مكة بصحبة تربوية وإشراف واضح.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ $bookingUrl }}"
                            class="inline-flex min-h-12 items-center justify-center gap-2 whitespace-nowrap rounded-sm bg-white px-6 py-3 text-sm font-bold text-[#0b211d] shadow-lg shadow-black/14 transition hover:-translate-y-0.5 hover:bg-[#eaf8f3] active:translate-y-px motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                            <span>احجز مقعدًا</span>
                            <x-hugeicon name="check-list" class="text-lg" />
                        </a>
                        <a href="#program"
                            class="inline-flex min-h-12 items-center justify-center gap-2 whitespace-nowrap rounded-sm border border-white/22 bg-white/5 px-6 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-white/10 active:translate-y-px motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                            <span>شاهد البرنامج</span>
                            <x-hugeicon name="arrow-left-02" class="text-lg" />
                        </a>
                    </div>
                </div>

                <div
                    class="overflow-hidden rounded-sm border border-white/12 bg-white/5 shadow-[0_34px_100px_rgba(0,0,0,0.32)]">
                    <img src="{{ $heroImage }}" alt="فتيان عُمانيون يتجهون إلى حافلة الرحلة عند الفجر"
                        width="1536" height="1024" fetchpriority="high" class="aspect-[3/2] h-full w-full object-cover">
                </div>
            </div>
        </section>

        <section class="border-b border-[#2a8069]/12 bg-white/68 dark:border-white/10 dark:bg-white/[0.03]">
            <dl class="mx-auto grid w-full max-w-7xl gap-px px-4 py-5 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
                <div class="px-4 py-5 sm:border-s sm:border-[#2a8069]/12 dark:sm:border-white/10">
                    <dt class="text-sm text-[#315e52]/72 dark:text-[#d2e7df]/58">الموعد</dt>
                    <dd class="mt-1 font-heading text-xl font-bold text-[#123329] dark:text-[#f7f1df]">٢٠ إلى ٢٩ أغسطس ٢٠٢٦</dd>
                </div>
                <div class="px-4 py-5 sm:border-s sm:border-[#2a8069]/12 dark:sm:border-white/10">
                    <dt class="text-sm text-[#315e52]/72 dark:text-[#d2e7df]/58">الفئة العمرية</dt>
                    <dd class="mt-1 font-heading text-xl font-bold text-[#123329] dark:text-[#f7f1df]">١٦ إلى ١٨ سنة</dd>
                </div>
                <div class="px-4 py-5 sm:border-s sm:border-[#2a8069]/12 dark:sm:border-white/10">
                    <dt class="text-sm text-[#315e52]/72 dark:text-[#d2e7df]/58">المقاعد المتبقية</dt>
                    <dd class="mt-1 font-heading text-xl font-bold text-[#007a52] dark:text-[#6ee7b7]">{{ $remainingSeats }} من {{ $event->seat_capacity }}</dd>
                </div>
                <div class="px-4 py-5">
                    <dt class="text-sm text-[#315e52]/72 dark:text-[#d2e7df]/58">السعر للفرد</dt>
                    <dd class="mt-1 font-heading text-xl font-bold text-[#123329] dark:text-[#f7f1df]">
                        <x-money :amount-baisa="$event->price_baisa" :currency="$event->currency" />
                    </dd>
                </div>
            </dl>
        </section>

        <section
            class="mx-auto grid w-full max-w-7xl gap-10 px-4 py-18 sm:px-6 lg:grid-cols-[0.78fr_1.22fr] lg:items-center lg:px-8 lg:py-24">
            <div class="overflow-hidden rounded-sm border border-[#2a8069]/14 bg-white dark:border-white/10 dark:bg-white/5">
                <img src="{{ $preparationImage }}" alt="جلسة تهيئة بين مرشد وفتيين قبل الرحلة" width="864"
                    height="1821" loading="lazy" class="aspect-[4/5] h-full max-h-[38rem] w-full object-cover">
            </div>
            <div class="max-w-2xl">
                <h2 class="font-heading text-3xl font-bold leading-tight text-[#123329] lg:text-5xl dark:text-[#f7f1df]">
                    لا ننتقل من البيت إلى الحرم مباشرة
                </h2>
                <p class="mt-5 leading-8 text-[#315e52] dark:text-[#d2e7df]/76">
                    تبدأ الرحلة بثلاثة أيام في بيرحاء. يتعلم الفتى فيها صفة العمرة، ويعرف مجموعته ومشرفه، ويرتب نيته وأوراقه وإيقاع يومه قبل السفر.
                </p>
                <blockquote
                    class="mt-7 border-s-2 border-[#007a52] py-2 ps-5 text-lg font-semibold leading-8 text-[#123329] dark:border-[#6ee7b7] dark:text-[#f7f1df]">
                    التهيئة تجعل العبادة مفهومة، والصحبة مألوفة، والطريق أهدأ على الفتى وولي أمره.
                </blockquote>
            </div>
        </section>

        <section class="relative border-y border-[#2a8069]/12 bg-white/64 dark:border-white/10 dark:bg-white/[0.03]">
            <span id="program" class="pointer-events-none absolute -top-20" aria-hidden="true"></span>
            <div class="mx-auto w-full max-w-7xl px-4 py-18 sm:px-6 lg:px-8 lg:py-24">
                <div class="max-w-3xl">
                    <h2 class="font-heading text-3xl font-bold text-[#123329] lg:text-5xl dark:text-[#f7f1df]">من إبراء إلى مكة، ثم العودة</h2>
                    <p class="mt-4 leading-8 text-[#315e52] dark:text-[#d2e7df]/76">هذا هو المسار المعتمد. أما ساعات الطريق والتجمع فتُسلّم للأسرة بعد تثبيت الترتيبات.</p>
                </div>

                <div class="mt-10 grid grid-cols-12 gap-4">
                    @foreach ($phases as $phase)
                        <article
                            class="public-card col-span-12 {{ $phase['class'] }} rounded-sm border border-[#2a8069]/14 bg-white/78 p-6 dark:border-white/10 dark:bg-white/5 sm:p-7">
                            <div class="flex items-start gap-4">
                                <span
                                    class="flex size-12 shrink-0 items-center justify-center rounded-sm bg-[#007a52]/10 text-[#007a52] dark:bg-[#6ee7b7]/10 dark:text-[#6ee7b7]">
                                    <x-hugeicon :name="$phase['icon']" class="text-2xl" />
                                </span>
                                <div>
                                    <p class="text-sm font-bold text-[#007a52] dark:text-[#6ee7b7]">{{ $phase['date'] }}</p>
                                    <h3 class="mt-2 font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">{{ $phase['title'] }}</h3>
                                    <p class="mt-3 max-w-[56ch] leading-7 text-[#315e52] dark:text-[#d2e7df]/72">{{ $phase['body'] }}</p>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="mx-auto w-full max-w-7xl px-4 py-18 sm:px-6 lg:px-8 lg:py-24">
            <div class="overflow-hidden rounded-sm bg-[#0d2b25] text-white shadow-[0_26px_80px_rgba(18,51,41,0.16)]">
                <div class="grid gap-10 p-7 sm:p-10 lg:grid-cols-[0.72fr_1.28fr] lg:p-14">
                    <div>
                        <h2 class="font-heading text-3xl font-bold leading-tight lg:text-4xl">طمأنينة مبنية على ترتيب واضح</h2>
                        <p class="mt-4 leading-8 text-white/72">السفر مع فتيان أمانة. لذلك تبدأ السلامة قبل الطريق، من جمع المعلومات وتوزيع المسؤوليات وإشراك ولي الأمر.</p>
                    </div>
                    <div class="grid gap-x-8 gap-y-7 sm:grid-cols-2">
                        @foreach ($safetyPoints as $point)
                            <article class="border-t border-white/14 pt-5">
                                <div class="flex items-start gap-4">
                                    <x-hugeicon :name="$point['icon']" class="mt-1 text-2xl text-[#87d9bd]" />
                                    <div>
                                        <h3 class="font-heading text-xl font-bold">{{ $point['title'] }}</h3>
                                        <p class="mt-2 leading-7 text-white/68">{{ $point['body'] }}</p>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="border-y border-[#2a8069]/12 bg-white/64 dark:border-white/10 dark:bg-white/[0.03]">
            <div class="mx-auto grid w-full max-w-7xl gap-10 px-4 py-18 sm:px-6 lg:grid-cols-[0.82fr_1.18fr] lg:px-8 lg:py-24">
                <div class="lg:sticky lg:top-28 lg:self-start">
                    <h2 class="font-heading text-3xl font-bold text-[#123329] lg:text-5xl dark:text-[#f7f1df]">سعر واحد داخل النظام</h2>
                    <div class="mt-6 rounded-sm bg-[#0d2b25] p-7 text-white">
                        <p class="text-sm text-white/62">السعر للفرد</p>
                        <p class="mt-2 font-heading text-4xl font-bold"><x-money :amount-baisa="$event->price_baisa" :currency="$event->currency" /></p>
                        <p class="mt-3 text-sm leading-7 text-white/68">أي خصم أو خطة سداد تعتمدها الإدارة تظهر داخل الحجز قبل إرسال الطلب.</p>
                    </div>
                    <a href="{{ $bookingUrl }}"
                        class="mt-4 inline-flex min-h-12 w-full items-center justify-center gap-2 whitespace-nowrap rounded-sm bg-[#007a52] px-6 py-3 text-sm font-bold text-white transition hover:bg-[#006746] active:translate-y-px">
                        <span>احجز مقعدًا</span>
                        <x-hugeicon name="check-list" class="text-lg" />
                    </a>
                </div>

                <div class="grid gap-8 sm:grid-cols-2">
                    <div>
                        <h3 class="font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">يشمل السعر</h3>
                        <ul class="mt-5 grid gap-4 text-[#315e52] dark:text-[#d2e7df]/76">
                            @foreach (['إقامة أيام التهيئة في مخيم بيرحاء', 'النقل البري ومحطات الطريق', 'السكن والوجبات خلال البرنامج', 'الإشراف والبرنامج التربوي'] as $item)
                                <li class="flex gap-3">
                                    <x-hugeicon name="checkmark-circle-01" class="mt-1 shrink-0 text-xl text-[#007a52] dark:text-[#6ee7b7]" />
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div>
                        <h3 class="font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">لا يشمل السعر</h3>
                        <ul class="mt-5 grid gap-4 text-[#315e52] dark:text-[#d2e7df]/76">
                            @foreach (['إصدار جواز السفر أو تجديده', 'المشتريات والهدايا الشخصية', 'الخدمات الإضافية خارج البرنامج', 'أي رسوم تنتج عن مخالفة فردية'] as $item)
                                <li class="flex gap-3">
                                    <x-hugeicon name="cancel-circle" class="mt-1 shrink-0 text-xl text-[#315e52]/62 dark:text-[#d2e7df]/58" />
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto w-full max-w-4xl px-4 py-18 sm:px-6 lg:py-24">
            <h2 class="font-heading text-3xl font-bold text-[#123329] lg:text-5xl dark:text-[#f7f1df]">أسئلة ولي الأمر</h2>
            <div class="mt-9 grid gap-3">
                @foreach ($faqs as $faq)
                    <details
                        class="group rounded-sm border border-[#2a8069]/14 bg-white/72 p-5 open:border-[#007a52]/34 dark:border-white/10 dark:bg-white/5 dark:open:border-[#6ee7b7]/28">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-heading text-xl font-bold text-[#123329] dark:text-[#f7f1df]">
                            <span>{{ $faq['question'] }}</span>
                            <span class="text-2xl text-[#007a52] group-open:rotate-45 dark:text-[#6ee7b7]">+</span>
                        </summary>
                        <p class="mt-4 max-w-[65ch] border-t border-[#2a8069]/12 pt-4 leading-8 text-[#315e52] dark:border-white/10 dark:text-[#d2e7df]/74">
                            {{ $faq['answer'] }}
                        </p>
                    </details>
                @endforeach
            </div>
        </section>

        <section class="bg-[#0d2b25] text-white">
            <div class="mx-auto flex w-full max-w-7xl flex-col gap-7 px-4 py-14 sm:px-6 lg:flex-row lg:items-center lg:justify-between lg:px-8 lg:py-16">
                <div>
                    <h2 class="font-heading text-3xl font-bold lg:text-4xl">ثبّت مقعد ابنك من النظام نفسه</h2>
                    <p class="mt-3 max-w-2xl leading-8 text-white/72">ينقلك الزر إلى حساب ولي الأمر، ثم تختار الابن وتراجع السعر قبل إرسال الطلب.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ $bookingUrl }}"
                        class="inline-flex min-h-12 items-center justify-center gap-2 whitespace-nowrap rounded-sm bg-white px-6 py-3 text-sm font-bold text-[#0d2b25] transition hover:-translate-y-0.5 hover:bg-[#eaf8f3] active:translate-y-px motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                        <span>احجز مقعدًا</span>
                        <x-hugeicon name="check-list" class="text-lg" />
                    </a>
                    <a href="{{ route('events.index') }}"
                        class="inline-flex min-h-12 items-center justify-center gap-2 whitespace-nowrap rounded-sm border border-white/22 px-6 py-3 text-sm font-bold text-white transition hover:bg-white/10">
                        <span>كل الفعاليات</span>
                        <x-hugeicon name="calendar-03" class="text-lg" />
                    </a>
                </div>
            </div>
        </section>
    </article>
@endsection
