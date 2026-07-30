@php
    $heroImage = asset('images/umrah-2026-hero.png');
    $preparationImage = asset('images/umrah-2026-preparation-v2.webp');

    $arabicNumber = fn (int|string $value): string => strtr((string) $value, [
        '0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤',
        '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩',
    ]);

    $priceTiers = $event->priceTiers
        ->filter(fn ($tier): bool => $tier->is_active && $tier->price_baisa <= $event->price_baisa)
        ->values();
    $currentPriceTier = $priceTiers->first(
        fn ($tier): bool => max(0, $tier->seat_capacity - $tier->usedSeatsCount()) > 0,
    );
    $startingPriceBaisa = (int) ($priceTiers->first()?->price_baisa ?? $event->price_baisa);

    $whyPoints = [
        ['icon' => 'book-open-text', 'title' => 'العبادة تُعاش لا تُحفَظ', 'body' => 'يتعلّم الفتى المناسك في المخيم عمليًا، ثم يؤديها في الحرم وهو يفقه ما يفعل، لا يقلّد من أمامه.', 'class' => 'md:col-span-7'],
        ['icon' => 'map-pin', 'title' => 'سنّ المفترق', 'body' => 'في العاشر والحادي عشر والثاني عشر تُصنع القرارات الكبرى. ووقفة عند البيت الحرام تعيد ترتيب الأولويات قبل اختيار الطريق.', 'class' => 'md:col-span-5'],
        ['icon' => 'user-group', 'title' => 'صحبة تبقى', 'body' => 'ثلاثون فتى وقفوا في صف واحد وسافروا معًا، فتنشأ بينهم أخوة تعين على الخير بعد انتهاء الرحلة.', 'class' => 'md:col-span-5'],
        ['icon' => 'lock-key', 'title' => 'سفر آمن منضبط', 'body' => 'مرافقة ذكورية كاملة، ونسبة إشراف واضحة، وخطة طوارئ معتمدة قبل الانطلاق، فلا ارتجال في رحلة فيها أمانة.', 'class' => 'md:col-span-7'],
    ];

    $parentWorries = [
        ['question' => 'ابني لم يسافر بلا أهله من قبل', 'concern' => 'هذا أول ما يخطر ببال الأب، وهو قلق في محلّه.', 'answer' => 'ثلاثة أيام في المخيم قبل السفر تكسر الغربة وتصنع الألفة، ولكل مجموعة مشرف ثابت يرافقها من الوصول إلى التسليم.'],
        ['question' => 'كيف أطمئن عليه وهو بعيد؟', 'concern' => 'انقطاع الخبر أثقل على الأب من بُعد المسافة.', 'answer' => 'وقت تواصل يومي مجدول، ورسالة طمأنينة من الإدارة كل مساء، ورقم طوارئ يعمل على مدار الساعة طوال الرحلة.'],
        ['question' => 'ماذا لو مرض أو أُصيب؟', 'concern' => 'السفر متعب، والزحام في الحرم لا يُستهان به.', 'answer' => 'حقيبة إسعافات مع كل مجموعة، ومشرف مدرّب على الإسعاف الأولي، وتأمين سفر، وإبلاغ ولي الأمر فور أي عارض.'],
        ['question' => 'الكلفة ثقيلة على ميزانيتي', 'concern' => 'وهذا صدق نحترمه ولا نحرج به أحدًا.', 'answer' => 'تسعير متدرج يبدأ من أقل سعر، وسداد بعربون ثم دفعتين، ومقاعد رحمة مدعومة تُطلب بسرية من الإدارة.'],
        ['question' => 'ابني ليس ملتزمًا بما يكفي', 'concern' => 'بعض الآباء يخشى ألا يكون ابنه أهلًا للرحلة.', 'answer' => 'الرحلة ليست جائزة للصالحين، بل باب لمن يريد أن يبدأ. نأخذ الفتى من حيث هو، بلا تعنيف ولا مقارنة بغيره.'],
        ['question' => 'الجوازات والإجراءات ترهقني', 'concern' => 'التأشيرات والتصاريح وموافقات السفر تفاصيل متعبة.', 'answer' => 'ترسل الإدارة قائمة واضحة بما يلزم ولي الأمر، وتنسق بقية إجراءات الرحلة وفق المتطلبات المعتمدة.'],
    ];

    $itinerary = [
        ['day' => 'الخميس', 'date' => '٢٠ أغسطس ٢٠٢٦م', 'place' => 'مخيم بيرحاء', 'title' => 'الوصول إلى مخيم بيرحاء', 'body' => 'استقبال القادة وتسليم الأمانات وتوزيع المجموعات، ثم لقاء التعارف الأول مع أبي بلج وميثاق الرحلة.', 'items' => ['استقبال وتسجيل وتسليم جوازات السفر للأمانة', 'توزيع المجموعات وتعيين المشرف المرافق لكل منها', 'ميثاق القادة: ما لي وما عليّ في هذه الرحلة', 'عشاء جماعي وسهرة افتتاح قصيرة ثم نوم']],
        ['day' => 'الجمعة', 'date' => '٢١ أغسطس ٢٠٢٦م', 'place' => 'مخيم بيرحاء', 'title' => 'يوم في رحاب بيرحاء', 'body' => 'يوم كامل على إيقاع خماسية السكينة، وفي وسطه صلاة الجمعة ودرس عملي في فقه العمرة وصفتها.', 'items' => ['قيام وقرآن الفجر وأذكار الصباح', 'حلقة فقه العمرة: الإحرام والمواقيت والمحظورات', 'صلاة الجمعة في مسجد المنطقة', 'تطبيق عملي لصفة الطواف والسعي', 'رياضة مسائية ولقاء مفتوح مع أبي بلج']],
        ['day' => 'السبت', 'date' => '٢٢ أغسطس ٢٠٢٦م', 'place' => 'مخيم بيرحاء', 'title' => 'اليوم التحضيري الأخير', 'body' => 'إتقان ما تعلمه القادة، وضبط الحقائب والأوراق، وجلسة «نيتي في هذه الرحلة»، فما لكل امرئ إلا ما نوى.', 'items' => ['مراجعة المناسك عمليًا حتى الإتقان', 'جلسة النية وكتابة الدعوات التي يحملها كل قائد', 'مراجعة الجوازات والتصاريح وقوائم التجهيز', 'نشاط ترفيهي ثم نوم مبكر استعدادًا للسفر']],
        ['day' => 'الأحد', 'date' => '٢٣ أغسطس ٢٠٢٦م', 'place' => 'الطريق', 'title' => 'الانطلاق إلى مكة المكرمة', 'body' => 'انطلاق بري من مخيم بيرحاء بعد الفجر، مع محطات راحة وصلاة منظمة على الطريق.', 'items' => ['تحميل الحقائب والتأكد من اكتمال العدد', 'انطلاق بعد صلاة الفجر مع أذكار السفر', 'محطات راحة وطعام وصلاة مجدولة', 'مبيت استراحة على الطريق بإشراف كامل']],
        ['day' => 'الإثنين', 'date' => '٢٤ أغسطس ٢٠٢٦م', 'place' => 'مكة المكرمة', 'title' => 'الوصول إلى مكة المكرمة', 'body' => 'الوصول والاستقرار في السكن، ثم الإحرام وأداء العمرة بصحبة أبي بلج إن سمحت حال القادة، وإلا أُخرت إلى ما بعد الراحة.', 'items' => ['الوصول والاستقرار وتوزيع الغرف', 'راحة ووجبة قبل النسك', 'الإحرام والتلبية ثم الطواف والسعي', 'الحلق أو التقصير، وتمام العمرة بإذن الله']],
        ['day' => 'الثلاثاء والأربعاء', 'date' => '٢٥ و٢٦ أغسطس ٢٠٢٦م', 'place' => 'مكة المكرمة', 'title' => 'يومان في رحاب البيت العتيق', 'body' => 'صلوات في الحرم، وحلق قصيرة بعد الصلوات، وزيارات للمعالم المأثورة، بجدول يوازن بين العبادة وراحة الفتى.', 'items' => ['الصلوات الخمس في المسجد الحرام جماعة', 'حلق أبي بلج بعد الفجر والعصر', 'طواف ودعاء ووِرد يومي لكل قائد', 'زيارات ميدانية للمعالم المأثورة بإشراف كامل', 'وقت مفتوح للتواصل مع الأهل يوميًا']],
        ['day' => 'الخميس', 'date' => '٢٧ أغسطس ٢٠٢٦م', 'place' => 'مكة المكرمة', 'title' => 'طواف الوداع بعد صلاة العصر', 'body' => 'يُختتم المقام بطواف الوداع بعد صلاة العصر، ثم يُستأنف طريق العودة بعد المغرب بإذن الله.', 'items' => ['حقيبة معدة ومغادرة السكن قبل العصر', 'صلاة العصر في الحرم', 'طواف الوداع ودعاء الختام', 'الانطلاق عائدين بعد المغرب بإذن الله']],
        ['day' => 'الجمعة', 'date' => '٢٨ أغسطس ٢٠٢٦م', 'place' => 'طريق العودة', 'title' => 'يوم على الطريق', 'body' => 'يوم عودة منظم بمحطات راحة وصلاة، وجلسة تقويم جماعية في الحافلة: ماذا أخذت؟ وبم أعود؟', 'items' => ['محطات راحة وصلاة مجدولة', 'جلسة «بم أعود؟» وكتابة خطة ما بعد الرحلة', 'اطمئنان هاتفي على الأهل من الطريق']],
        ['day' => 'السبت', 'date' => '٢٩ أغسطس ٢٠٢٦م', 'place' => 'مخيم بيرحاء', 'title' => 'الوصول واللقاء الختامي', 'body' => 'الوصول والاستقبال، ولقاء ختام قصير يُسلّم فيه كل قائد إلى وليه، ومعه خطته المكتوبة بيده.', 'items' => ['الوصول وتسليم الأمانات والجوازات', 'لقاء ختام قصير وتكريم القادة', 'تسليم كل قائد إلى وليه مباشرة', 'انضمام القادة إلى مجموعة المتابعة بعد الرحلة']],
    ];

    $fiveRhythms = [
        ['icon' => 'moon-02', 'title' => 'العبادة', 'body' => 'قيام وقرآن فجر وأذكار، وفقه العمرة عمليًا حتى الإتقان.', 'class' => 'md:col-span-5 md:row-span-2'],
        ['icon' => 'book-open-text', 'title' => 'العلم', 'body' => 'حلق قصيرة في صفة النسك وأحكام السفر، وقراءة موجهة.', 'class' => 'md:col-span-7'],
        ['icon' => 'check-list', 'title' => 'العمل', 'body' => 'يشترك القادة في إعداد وجباتهم وترتيب مواضعهم، فلا خدم في بيرحاء.', 'class' => 'md:col-span-3'],
        ['icon' => 'user-group', 'title' => 'اللعب', 'body' => 'رياضة صباحية ومسائية وأنشطة جماعية تصنع الألفة قبل السفر.', 'class' => 'md:col-span-4'],
        ['icon' => 'clock-01', 'title' => 'النوم والصحة', 'body' => 'نوم مبكر منضبط، وغذاء متوازن، وضبط الساعة البيولوجية قبل الطريق.', 'class' => 'md:col-span-7'],
    ];

    $outcomes = [
        ['number' => '١', 'title' => 'عمرة متقنة لا مقلدة', 'body' => 'يعود وقد أدى النسك وهو يعرف دليله وصفته، ويستطيع أن يعلّمها إخوته في البيت.'],
        ['number' => '٢', 'title' => 'خطة مكتوبة بيده', 'body' => 'يكتب في طريق العودة ما سيبدأ به: وِرده اليومي، وصلاته، وهدف واحد يبدأ به عامه الدراسي.'],
        ['number' => '٣', 'title' => 'صحبة ومجموعة متابعة', 'body' => 'ينضم إلى مجموعة قادة بيرحاء بعد الرحلة، ولقاءات تحيي الرابطة وتثبّت الأثر.'],
        ['number' => '٤', 'title' => 'ثقة باستقلاله', 'body' => 'يعود وقد سافر ورتّب حقيبته وحفظ مواعيده وحمل مسؤولية نفسه عشرة أيام.'],
    ];

    $safetyPoints = [
        ['icon' => 'user-group', 'title' => 'نسبة إشراف لا تُساوم', 'body' => 'مشرف مؤهل لكل ثمانية فتيان كحد أقصى، والمرافقة ذكورية كاملة.'],
        ['icon' => 'lock-key', 'title' => 'خطة طوارئ معتمدة', 'body' => 'مسار بديل، ومستشفيات مرجعية على الطريق، وسلسلة اتصال واضحة لكل حالة.'],
        ['icon' => 'file-view', 'title' => 'تأمين وملف صحي', 'body' => 'تأمين سفر ساري طوال الرحلة، وملف صحي يعبئه ولي الأمر قبل الانطلاق.'],
        ['icon' => 'mail-01', 'title' => 'تواصل يومي مجدول', 'body' => 'وقت محدد للاتصال بالأهل، ورسالة مسائية من الإدارة، ورقم طوارئ على مدار الساعة.'],
        ['icon' => 'contracts', 'title' => 'عقد وموافقة خطية', 'body' => 'لا يسافر قاصر إلا بعقد موقع وموافقة خطية من وليه، وجواز صالح لستة أشهر فأكثر.'],
        ['icon' => 'view-off', 'title' => 'خصوصية القُصّر', 'body' => 'لا تُنشر صورة وجه أو اسم كامل على واجهة عامة إلا بموافقة خطية من ولي الأمر.'],
    ];

    $included = [
        'إقامة ثلاثة أيام كاملة في مخيم بيرحاء، مع السكن والوجبات',
        'النقل البري المكيف ذهابًا وإيابًا مع محطات الراحة',
        'السكن في مكة المكرمة طوال أيام الإقامة',
        'الوجبات اليومية طوال الرحلة',
        'برنامج الحلق والزيارات بصحبة أبي بلج',
        'تأمين السفر وحقيبة الإسعافات لكل مجموعة',
        'حقيبة الرحلة: إحرام ودليل المناسك ودفتر القائد',
        'الإشراف التربوي على مدار الساعة',
    ];

    $notIncluded = [
        'رسوم إصدار جواز السفر أو تجديده',
        'المشتريات والهدايا الشخصية',
        'الخدمات الفندقية الإضافية خارج البرنامج',
        'ما يترتب على مخالفة فردية لأنظمة السفر',
    ];

    $kitGroups = [
        ['title' => 'الأوراق أولًا', 'items' => ['جواز سفر صالح لستة أشهر فأكثر', 'صورة البطاقة الشخصية', 'الموافقة الخطية الموقعة من ولي الأمر', 'الملف الصحي معبأً']],
        ['title' => 'اللباس', 'items' => ['ملابس إحرام، وتُزوّد ضمن الحقيبة', 'ثياب خفيفة محتشمة لعشرة أيام', 'حذاء مريح للمشي ونعل للحرم', 'ثوب للصلاة ولباس رياضي لأيام المخيم']],
        ['title' => 'العناية الشخصية', 'items' => ['أدوات نظافة خالية من الطيب لأيام الإحرام', 'مظلة صغيرة أو قبعة للشمس', 'قارورة ماء قابلة لإعادة التعبئة', 'الأدوية الخاصة، وتُسلّم للمشرف']],
        ['title' => 'ما ينفع القلب', 'items' => ['مصحف صغير أو تطبيق عليه', 'دفتر صغير وقلم لكتابة الدعوات والخواطر', 'كتاب خفيف لقراءة الطريق']],
        ['title' => 'ما لا يُحمل', 'items' => ['مبالغ نقدية كبيرة، وتُسلّم للمشرف أمانة', 'أجهزة ثمينة لا يحتاجها', 'أي صنف يخالف أنظمة السفر والمنافذ']],
    ];

    $faqs = [
        ['question' => 'لمن هذه الرحلة بالضبط؟', 'answer' => 'للفتيان من طلاب الصفوف العاشر والحادي عشر والثاني عشر، من ١٦ إلى ١٨ عامًا تقريبًا. والذكورة شرط في المستفيدين والمرافقين لطبيعة الإقامة الكاملة.'],
        ['question' => 'لماذا ثلاثة أيام في المخيم قبل السفر؟', 'answer' => 'هذه الأيام هي فرق ما بين رحلة سياحية ورحلة تربوية. يتعلم القائد المناسك عمليًا، ويتعرف على مجموعته ومشرفه، ويضبط إيقاع نومه وعبادته، فيصل إلى مكة مستعدًا.'],
        ['question' => 'كيف أتواصل مع ابني أثناء الرحلة؟', 'answer' => 'يخصص وقت يومي للاتصال بالأهل، وترسل الإدارة رسالة طمأنينة كل مساء إلى مجموعة أولياء الأمور، مع رقم طوارئ طوال الرحلة.'],
        ['question' => 'ما نسبة الإشراف؟ ومن يرافق ابني؟', 'answer' => 'مشرف مؤهل لكل ثمانية فتيان كحد أقصى، ولكل مجموعة مشرف ثابت من يوم الوصول إلى يوم التسليم، يعرفه ابنك بالاسم وتعرفه أنت.'],
        ['question' => 'ماذا لو مرض ابني أو أُصيب؟', 'answer' => 'مع كل مجموعة حقيبة إسعافات ومشرف مدرب على الإسعاف الأولي، ولكل قائد تأمين سفر. ويُبلّغ ولي الأمر بأي عارض، ولا يُتخذ قرار طبي إلا بعلمه ما لم تكن الحالة إسعافية عاجلة.'],
        ['question' => 'كيف يُسدّد المبلغ؟ وهل يمكن التقسيط؟', 'answer' => 'يُثبت المقعد بدفع قسط أو المبلغ كاملًا عبر النظام. وتظهر خطط السداد المتاحة ومواعيدها داخل الحجز قبل الدفع.'],
        ['question' => 'ما مقاعد الرحمة؟ وكيف تُطلب؟', 'answer' => 'نسبة من المقاعد مدعومة لمن تحول ظروفه دون الرسوم كاملة. تُطلب برسالة خاصة إلى الإدارة، ولا يعلم بها إلا من يبت فيها.'],
        ['question' => 'ماذا لو اكتملت المقاعد؟', 'answer' => 'لا نقبل حجزًا زائدًا، فسقف المقاعد التزام جودة. وعند اكتمالها يمكن للإدارة ترتيب قائمة انتظار والتواصل عند شغور مقعد.'],
        ['question' => 'هل تُنشر لابني صورة أو اسم؟', 'answer' => 'لا تُنشر صورة وجه ولا اسم كامل على واجهة عامة إلا بموافقة ولي الأمر الخطية، وفق ضوابط حماية البيانات الشخصية.'],
        ['question' => 'وماذا بعد الرحلة؟', 'answer' => 'ينضم القائد إلى مجموعة متابعة بعد العودة، ومعه خطته التي كتبها بيده، ويُدعى إلى فعاليات بيرحاء الممتدة طوال العام. فالرحلة بداية وليست خاتمة.'],
    ];
@endphp

@extends('layouts.public', [
    'title' => $title,
    'metaDescription' => $metaDescription,
    'metaImage' => $heroImage,
])

@section('content')
    <article class="overflow-hidden">
        <section class="relative isolate min-h-[calc(100dvh-5rem)] overflow-hidden">
            <div class="pointer-events-none absolute inset-0 -z-20 bg-[#eff9f5] dark:bg-[#07120f]"></div>
            <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_84%_8%,rgba(0,144,96,0.19),transparent_32%),radial-gradient(circle_at_12%_80%,rgba(224,168,0,0.12),transparent_28%)] dark:bg-[radial-gradient(circle_at_84%_8%,rgba(52,211,153,0.15),transparent_32%),radial-gradient(circle_at_12%_80%,rgba(224,168,0,0.08),transparent_28%)]"></div>

            <div class="mx-auto grid min-h-[calc(100dvh-5rem)] w-full max-w-7xl items-center gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,0.86fr)_minmax(32rem,1.14fr)] lg:px-8 lg:py-16">
                <div class="max-w-2xl">
                    <a href="{{ route('events.index') }}" class="inline-flex min-h-10 items-center gap-2 rounded-full border border-[#007a52]/18 bg-white/68 px-4 py-2 text-sm font-bold text-[#007a52] transition hover:border-[#007a52]/32 hover:bg-white dark:border-[#6ee7b7]/22 dark:bg-white/5 dark:text-[#6ee7b7] dark:hover:bg-white/10">
                        <x-hugeicon name="calendar-03" class="text-lg" />
                        فعالية بيرحاء الخارجية
                    </a>

                    <h1 class="mt-6 max-w-[11ch] font-heading text-5xl font-bold leading-[1.14] text-[#0d2b25] sm:text-6xl lg:text-7xl dark:text-[#f7f1df]">
                        رحلة العمرة بصحبة أبي بلج
                    </h1>

                    <p class="mt-6 max-w-[42rem] text-lg leading-8 text-[#315e52] dark:text-[#d2e7df]/76">
                        ثلاثة أيام تهيئة في بيرحاء، ثم طريق منظم إلى مكة لقادة الصفوف العاشر والحادي عشر والثاني عشر.
                    </p>

                    <div class="mt-8 flex flex-wrap gap-3">
                        <x-public-event-interest-action :event="$event" button-class="inline-flex min-h-12 items-center justify-center gap-2 whitespace-nowrap rounded-full bg-[#007a52] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#007a52]/18 transition hover:-translate-y-0.5 hover:bg-[#006746] active:translate-y-px motion-reduce:transition-none motion-reduce:hover:translate-y-0" />
                        <a href="#program" class="inline-flex min-h-12 items-center justify-center gap-2 whitespace-nowrap rounded-full border border-[#007a52]/22 bg-white/72 px-6 py-3 text-sm font-bold text-[#0d2b25] transition hover:-translate-y-0.5 hover:bg-white active:translate-y-px motion-reduce:transition-none motion-reduce:hover:translate-y-0 dark:border-white/16 dark:bg-white/5 dark:text-white dark:hover:bg-white/10">
                            <span>البرنامج يومًا بيوم</span>
                            <x-hugeicon name="arrow-left-02" class="text-lg" />
                        </a>
                    </div>
                </div>

                <div class="relative lg:ps-6">
                    <div class="absolute -inset-5 -z-10 rounded-[2rem] bg-[#007a52]/8 blur-2xl dark:bg-[#6ee7b7]/8"></div>
                    <div class="overflow-hidden rounded-2xl border border-[#007a52]/12 bg-white/54 p-2 shadow-[0_34px_100px_rgba(18,51,41,0.18)] dark:border-white/10 dark:bg-white/5 dark:shadow-black/30">
                        <img src="{{ $heroImage }}" alt="فتيان عُمانيون يتجهون إلى حافلة الرحلة عند الفجر" width="1536" height="1024" fetchpriority="high" class="aspect-[3/2] w-full rounded-xl object-cover">
                    </div>
                </div>
            </div>
        </section>

        <section class="border-y border-[#2a8069]/12 bg-white/64 dark:border-white/10 dark:bg-white/[0.03]">
            <dl class="mx-auto grid w-full max-w-7xl gap-px px-4 py-6 sm:grid-cols-2 sm:px-6 lg:grid-cols-5 lg:px-8">
                @foreach ([
                    ['label' => 'القائد', 'value' => 'أبو بلج عبدالله بن عامر العيسري'],
                    ['label' => 'الموعد', 'value' => '٢٠ إلى ٢٩ أغسطس ٢٠٢٦م'],
                    ['label' => 'المدة', 'value' => '١٠ أيام'],
                    ['label' => 'الفئة', 'value' => 'فتيان ١٦ إلى ١٨ سنة'],
                    ['label' => 'المتبقي', 'value' => $arabicNumber($remainingSeats).' من '.$arabicNumber($event->seat_capacity).' مقعدًا'],
                ] as $fact)
                    <div class="px-4 py-4 lg:border-s lg:border-[#2a8069]/12 lg:first:border-s-0 dark:lg:border-white/10">
                        <dt class="text-sm text-[#315e52]/68 dark:text-[#d2e7df]/58">{{ $fact['label'] }}</dt>
                        <dd class="mt-1 font-heading text-lg font-bold leading-7 text-[#123329] dark:text-[#f7f1df]">{{ $fact['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>

        <section class="mx-auto grid w-full max-w-7xl gap-12 px-4 py-20 sm:px-6 lg:grid-cols-[0.74fr_1.26fr] lg:items-center lg:px-8 lg:py-28">
            <div class="relative overflow-hidden rounded-2xl border border-[#2a8069]/14 bg-white p-2 dark:border-white/10 dark:bg-white/5">
                <img src="{{ $preparationImage }}" alt="مرشد عُماني يهيئ مجموعة من الفتيان لمناسك العمرة" width="1122" height="1402" loading="lazy" class="aspect-[4/5] w-full rounded-xl object-cover">
            </div>
            <div class="max-w-3xl">
                <p class="text-sm font-bold text-[#007a52] dark:text-[#6ee7b7]">التهيئة قبل الطريق</p>
                <h2 class="mt-3 font-heading text-4xl font-bold leading-tight text-[#123329] lg:text-6xl dark:text-[#f7f1df]">
                    لا نأخذ الفتى من بيته إلى الحرم رأسًا
                </h2>
                <p class="mt-6 text-lg leading-9 text-[#315e52] dark:text-[#d2e7df]/76">
                    نبدأ بثلاثة أيام في مخيم بيرحاء: يتعلم فيها المناسك، ويتعرف على صحبته، ويضبط إيقاعه على خماسية السكينة، ثم ينطلق وقد صار مستعدًا فتقع العمرة منه موقعها. والفتى في هذه السن على مفترق طريق، فأي زاد أنفع له من طواف ودعاء وصحبة صالحة؟
                </p>
                <blockquote class="mt-8 border-s-2 border-[#e0a800] py-2 ps-5 font-heading text-2xl font-bold leading-9 text-[#123329] dark:text-[#f7f1df]">
                    «لا نحفّظ، بل نُعيش. ولا نلقّن، بل نُمكّن»
                </blockquote>
            </div>
        </section>

        <section class="border-y border-[#2a8069]/12 bg-white/56 dark:border-white/10 dark:bg-white/[0.03]">
            <div class="mx-auto w-full max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
                <div class="max-w-3xl">
                    <h2 class="font-heading text-4xl font-bold text-[#123329] lg:text-6xl dark:text-[#f7f1df]">لماذا هذه الرحلة الآن؟</h2>
                    <p class="mt-5 text-lg leading-8 text-[#315e52] dark:text-[#d2e7df]/76">لأن هذه السن تصنع اختيارات الفتى، ولأن العبادة إذا عِيشت مع صحبة صالحة تركت أثرًا أبعد من أيام السفر.</p>
                </div>
                <div class="mt-12 grid grid-cols-12 gap-4">
                    @foreach ($whyPoints as $point)
                        <article class="col-span-12 {{ $point['class'] }} rounded-2xl border border-[#2a8069]/14 bg-white/80 p-6 transition hover:-translate-y-1 hover:border-[#007a52]/26 dark:border-white/10 dark:bg-white/5 dark:hover:border-[#6ee7b7]/24 sm:p-8 motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                            <x-hugeicon :name="$point['icon']" class="text-3xl text-[#007a52] dark:text-[#6ee7b7]" />
                            <h3 class="mt-6 font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">{{ $point['title'] }}</h3>
                            <p class="mt-3 max-w-[58ch] leading-8 text-[#315e52] dark:text-[#d2e7df]/72">{{ $point['body'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="mx-auto grid w-full max-w-7xl gap-12 px-4 py-20 sm:px-6 lg:grid-cols-[0.68fr_1.32fr] lg:px-8 lg:py-28">
            <div class="lg:sticky lg:top-28 lg:self-start">
                <h2 class="font-heading text-4xl font-bold leading-tight text-[#123329] lg:text-5xl dark:text-[#f7f1df]">نفهم ما يدور في خاطرك قبل أن تسأل</h2>
                <p class="mt-5 leading-8 text-[#315e52] dark:text-[#d2e7df]/76">إرسال ابنك في سفر خارج السلطنة قرار ثقيل. لذلك نضع المخاوف كما هي، وبإزاء كل منها ترتيب عملي.</p>
            </div>
            <div class="grid gap-3">
                @foreach ($parentWorries as $worry)
                    <details class="group rounded-2xl border border-[#2a8069]/14 bg-white/70 p-5 open:border-[#007a52]/30 open:bg-white dark:border-white/10 dark:bg-white/5 dark:open:border-[#6ee7b7]/24 dark:open:bg-white/[0.07] sm:p-6">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-5 font-heading text-xl font-bold text-[#123329] dark:text-[#f7f1df]">
                            <span>«{{ $worry['question'] }}»</span>
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-[#007a52]/9 text-xl text-[#007a52] transition group-open:rotate-45 dark:bg-[#6ee7b7]/10 dark:text-[#6ee7b7] motion-reduce:transition-none">+</span>
                        </summary>
                        <div class="mt-5 border-t border-[#2a8069]/12 pt-5 dark:border-white/10">
                            <p class="text-[#315e52]/76 dark:text-[#d2e7df]/66">{{ $worry['concern'] }}</p>
                            <p class="mt-3 leading-8 text-[#123329] dark:text-[#f7f1df]/84"><strong class="text-[#007a52] dark:text-[#6ee7b7]">ما نفعله:</strong> {{ $worry['answer'] }}</p>
                        </div>
                    </details>
                @endforeach
            </div>
        </section>

        <section id="program" class="scroll-mt-24 border-y border-[#2a8069]/12 bg-white/56 dark:border-white/10 dark:bg-white/[0.03]">
            <div class="mx-auto w-full max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
                <div class="max-w-4xl">
                    <p class="text-sm font-bold text-[#007a52] dark:text-[#6ee7b7]">البرنامج يومًا بيوم</p>
                    <h2 class="mt-3 font-heading text-4xl font-bold leading-tight text-[#123329] lg:text-6xl dark:text-[#f7f1df]">عشرة أيام من إبراء إلى مكة، ثم العودة</h2>
                    <p class="mt-5 text-lg leading-8 text-[#315e52] dark:text-[#d2e7df]/76">هذا هو الهيكل المعتمد. وتُسلّم تفاصيل الساعات داخل كل يوم لولي الأمر في حقيبة الرحلة قبل الانطلاق بأسبوعين.</p>
                </div>

                <div class="relative mt-14 grid gap-5 lg:ms-24">
                    <div class="absolute bottom-6 start-[1.65rem] top-6 hidden w-px bg-[#007a52]/20 sm:block dark:bg-[#6ee7b7]/18"></div>
                    @foreach ($itinerary as $index => $day)
                        <details class="group relative rounded-2xl border border-[#2a8069]/14 bg-white/80 p-5 open:border-[#007a52]/30 dark:border-white/10 dark:bg-white/5 dark:open:border-[#6ee7b7]/24 sm:ps-20" @if ($index === 0) open @endif>
                            <span class="absolute start-5 top-5 hidden size-7 items-center justify-center rounded-full bg-[#007a52] text-xs font-bold text-white ring-8 ring-[#f4faf7] sm:flex dark:bg-[#6ee7b7] dark:text-[#07120f] dark:ring-[#0c1915]">{{ $arabicNumber($index + 1) }}</span>
                            <summary class="cursor-pointer list-none">
                                <div class="flex items-start justify-between gap-5">
                                    <div>
                                        <p class="text-sm font-bold text-[#007a52] dark:text-[#6ee7b7]">{{ $day['day'] }}، {{ $day['date'] }} · {{ $day['place'] }}</p>
                                        <h3 class="mt-2 font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">{{ $day['title'] }}</h3>
                                        <p class="mt-3 max-w-3xl leading-8 text-[#315e52] dark:text-[#d2e7df]/72">{{ $day['body'] }}</p>
                                    </div>
                                    <span class="mt-1 flex size-9 shrink-0 items-center justify-center rounded-full bg-[#007a52]/9 text-xl text-[#007a52] transition group-open:rotate-45 dark:bg-[#6ee7b7]/10 dark:text-[#6ee7b7] motion-reduce:transition-none">+</span>
                                </div>
                            </summary>
                            <ul class="mt-5 grid gap-3 border-t border-[#2a8069]/12 pt-5 text-sm leading-7 text-[#315e52] sm:grid-cols-2 dark:border-white/10 dark:text-[#d2e7df]/72">
                                @foreach ($day['items'] as $item)
                                    <li class="flex gap-3">
                                        <x-hugeicon name="checkmark-circle-01" class="mt-1 shrink-0 text-lg text-[#007a52] dark:text-[#6ee7b7]" />
                                        <span>{{ $item }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endforeach
                </div>

                <div class="mt-8 rounded-2xl border border-[#e0a800]/24 bg-[#fff9e8] p-6 text-[#684d00] dark:border-[#e0a800]/18 dark:bg-[#e0a800]/8 dark:text-[#f3dda0]">
                    <p class="leading-8"><strong>ملحوظة صادقة:</strong> مواعيد الانطلاق والوصول تقديرية تتبع حركة الطريق وإجراءات المنافذ، ويُبلّغ ولي الأمر بأي تعديل فور ثبوته. وتؤدى المناسك في اليوم الذي تسمح به حالة القادة بعد الوصول، فلا نرهق فتى في عبادة.</p>
                </div>
            </div>
        </section>

        <section class="mx-auto w-full max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
            <div class="max-w-3xl">
                <h2 class="font-heading text-4xl font-bold text-[#123329] lg:text-6xl dark:text-[#f7f1df]">خماسية السكينة في أيام المخيم</h2>
                <p class="mt-5 text-lg leading-8 text-[#315e52] dark:text-[#d2e7df]/76">عبادة وعلم وعمل ولعب ونوم وصحة. ليست محاضرات تُلقى، بل أيام تُعاش.</p>
            </div>
            <div class="mt-12 grid grid-cols-12 gap-4">
                @foreach ($fiveRhythms as $rhythm)
                    <article class="col-span-12 {{ $rhythm['class'] }} rounded-2xl border border-[#2a8069]/14 bg-white/72 p-6 dark:border-white/10 dark:bg-white/5 sm:p-7">
                        <x-hugeicon :name="$rhythm['icon']" class="text-3xl text-[#007a52] dark:text-[#6ee7b7]" />
                        <h3 class="mt-5 font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">{{ $rhythm['title'] }}</h3>
                        <p class="mt-3 leading-8 text-[#315e52] dark:text-[#d2e7df]/72">{{ $rhythm['body'] }}</p>
                    </article>
                @endforeach
            </div>
            <p class="mt-7 max-w-4xl border-s-2 border-[#007a52] py-2 ps-5 leading-8 text-[#315e52] dark:border-[#6ee7b7] dark:text-[#d2e7df]/76"><strong class="text-[#123329] dark:text-[#f7f1df]">ضابط تربوي ملزم:</strong> لا مقارنة بين قائد وآخر. القياس ذاتي دائمًا، اليوم مقابل الأمس، والمنافسة ضد التسويف والغفلة لا ضد الرفاق.</p>
        </section>

        <section class="border-y border-[#2a8069]/12 bg-white/56 dark:border-white/10 dark:bg-white/[0.03]">
            <div class="mx-auto grid w-full max-w-7xl gap-12 px-4 py-20 sm:px-6 lg:grid-cols-[0.64fr_1.36fr] lg:px-8 lg:py-28">
                <div>
                    <h2 class="font-heading text-4xl font-bold leading-tight text-[#123329] lg:text-5xl dark:text-[#f7f1df]">ما الذي يبقى بعد أن تُطوى الحقائب؟</h2>
                    <p class="mt-5 leading-8 text-[#315e52] dark:text-[#d2e7df]/76">الرحلة لا تُقاس بالصور، بل بما يستقر في القلب واليد بعد شهر من العودة.</p>
                </div>
                <ol class="grid gap-7">
                    @foreach ($outcomes as $outcome)
                        <li class="grid gap-4 border-b border-[#2a8069]/12 pb-7 sm:grid-cols-[3rem_1fr] dark:border-white/10">
                            <span class="font-heading text-3xl font-bold text-[#e0a800]">{{ $outcome['number'] }}</span>
                            <div>
                                <h3 class="font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">{{ $outcome['title'] }}</h3>
                                <p class="mt-2 leading-8 text-[#315e52] dark:text-[#d2e7df]/72">{{ $outcome['body'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        <section class="mx-auto w-full max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
            <div class="overflow-hidden rounded-2xl border border-[#0d2b25]/10 bg-[#0d2b25] text-white shadow-[0_26px_80px_rgba(18,51,41,0.18)] dark:border-white/10">
                <div class="grid gap-12 p-7 sm:p-10 lg:grid-cols-[0.65fr_1.35fr] lg:p-14">
                    <div>
                        <h2 class="font-heading text-4xl font-bold leading-tight lg:text-5xl">ابنك في أيد أمينة، وهذا تفصيلها</h2>
                        <p class="mt-5 leading-8 text-white/72">السلامة ليست وعدًا في إعلان، بل بنود مكتوبة في خطة يعتمدها القائد التنفيذي قبل الانطلاق.</p>
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

        <section id="booking" class="scroll-mt-24 border-y border-[#2a8069]/12 bg-white/56 dark:border-white/10 dark:bg-white/[0.03]">
            <div class="mx-auto w-full max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
                <div class="grid gap-10 lg:grid-cols-[0.72fr_1.28fr] lg:items-end">
                    <div>
                        <p class="text-sm font-bold text-[#007a52] dark:text-[#6ee7b7]">المقاعد والرسوم</p>
                        <h2 class="mt-3 font-heading text-4xl font-bold leading-tight text-[#123329] lg:text-6xl dark:text-[#f7f1df]">الباقات: الأرخص أولًا</h2>
                    </div>
                    <p class="max-w-2xl text-lg leading-8 text-[#315e52] dark:text-[#d2e7df]/76">تبدأ المقاعد بأقل سعر، وعند نفاد باقة ينتقل الحجز تلقائيًا إلى التالية. كل رقم هنا يأتي من النظام نفسه.</p>
                </div>

                @if ($priceTiers->isNotEmpty())
                    <div class="mt-12 overflow-hidden rounded-2xl border border-[#2a8069]/14 bg-white/76 dark:border-white/10 dark:bg-white/5">
                        @foreach ($priceTiers as $tier)
                            @php
                                $tierRemainingSeats = max(0, $tier->seat_capacity - $tier->usedSeatsCount());
                                $isCurrentTier = $currentPriceTier?->is($tier) ?? false;
                                $tierStatus = $tierRemainingSeats === 0 ? 'نفدت' : ($isCurrentTier ? 'مفتوحة الآن' : 'تُفتح لاحقًا');
                                $tierNote = match ((int) $tier->position) {
                                    1 => 'للحجز المبكر جدًا',
                                    2 => 'تُفتح عند نفاد الباقة',
                                    default => 'آخر ما يُطرح',
                                };
                            @endphp
                            <article @class([
                                'grid gap-5 border-b border-[#2a8069]/12 p-6 last:border-b-0 sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-center sm:p-8 dark:border-white/10',
                                'bg-[#007a52]/6 dark:bg-[#6ee7b7]/6' => $isCurrentTier,
                            ])>
                                <div>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <h3 class="font-heading text-3xl font-bold text-[#123329] dark:text-[#f7f1df]">{{ $tier->name }}</h3>
                                        <span @class([
                                            'rounded-full px-3 py-1 text-xs font-bold',
                                            'bg-[#007a52] text-white dark:bg-[#6ee7b7] dark:text-[#07120f]' => $isCurrentTier,
                                            'bg-[#315e52]/8 text-[#315e52] dark:bg-white/8 dark:text-[#d2e7df]/68' => ! $isCurrentTier,
                                        ])>{{ $tierStatus }}</span>
                                    </div>
                                    <p class="mt-2 text-sm text-[#315e52]/72 dark:text-[#d2e7df]/62">{{ $tierNote }}</p>
                                </div>
                                <p class="font-heading text-3xl font-bold text-[#007a52] dark:text-[#6ee7b7]"><x-money :amount-baisa="$tier->price_baisa" :currency="$tier->currency" /></p>
                                <p class="min-w-32 text-sm text-[#315e52] dark:text-[#d2e7df]/72">{{ $arabicNumber($tierRemainingSeats) }} من {{ $arabicNumber($tier->seat_capacity) }} مقاعد متاحة</p>
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="mt-12 rounded-2xl border border-[#2a8069]/14 bg-white/76 p-8 dark:border-white/10 dark:bg-white/5">
                        <p class="font-heading text-3xl font-bold text-[#123329] dark:text-[#f7f1df]"><x-money :amount-baisa="$event->price_baisa" :currency="$event->currency" /></p>
                        <p class="mt-3 text-[#315e52] dark:text-[#d2e7df]/72">السعر الحالي يظهر داخل الحجز قبل الدفع.</p>
                    </div>
                @endif

                <div class="mt-8 grid gap-5 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div class="rounded-2xl border border-[#e0a800]/24 bg-[#fff9e8] p-6 text-[#684d00] dark:border-[#e0a800]/18 dark:bg-[#e0a800]/8 dark:text-[#f3dda0]">
                        <p class="leading-8"><strong>طريقة التثبيت:</strong> يُعد المقعد محجوزًا عند دفع قسط أو المبلغ كاملًا. وتظهر خطة السداد المتاحة ومواعيدها داخل النظام قبل الدفع.</p>
                    </div>
                    <x-public-event-interest-action :event="$event" button-class="inline-flex min-h-14 items-center justify-center gap-2 whitespace-nowrap rounded-full bg-[#007a52] px-8 py-4 font-bold text-white shadow-lg shadow-[#007a52]/16 transition hover:-translate-y-0.5 hover:bg-[#006746] active:translate-y-px motion-reduce:transition-none motion-reduce:hover:translate-y-0" />
                </div>

                <div class="mt-16 grid gap-10 lg:grid-cols-2">
                    <div>
                        <h3 class="font-heading text-3xl font-bold text-[#123329] dark:text-[#f7f1df]">يشمل السعر</h3>
                        <ul class="mt-6 grid gap-4 text-[#315e52] dark:text-[#d2e7df]/76">
                            @foreach ($included as $item)
                                <li class="flex gap-3"><x-hugeicon name="checkmark-circle-01" class="mt-1 shrink-0 text-xl text-[#007a52] dark:text-[#6ee7b7]" /><span>{{ $item }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                    <div>
                        <h3 class="font-heading text-3xl font-bold text-[#123329] dark:text-[#f7f1df]">لا يشمل السعر</h3>
                        <ul class="mt-6 grid gap-4 text-[#315e52] dark:text-[#d2e7df]/76">
                            @foreach ($notIncluded as $item)
                                <li class="flex gap-3"><x-hugeicon name="cancel-circle" class="mt-1 shrink-0 text-xl text-[#315e52]/58 dark:text-[#d2e7df]/52" /><span>{{ $item }}</span></li>
                            @endforeach
                        </ul>
                        <div class="mt-8 rounded-2xl bg-[#007a52]/7 p-6 text-sm leading-7 text-[#315e52] dark:bg-[#6ee7b7]/7 dark:text-[#d2e7df]/72">
                            <p><strong class="text-[#123329] dark:text-[#f7f1df]">مقاعد الرحمة:</strong> تُطلب بسرية من الإدارة لمن تحول ظروفه دون الرسوم كاملة.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto w-full max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
            <div class="max-w-3xl">
                <h2 class="font-heading text-4xl font-bold text-[#123329] lg:text-6xl dark:text-[#f7f1df]">ما يحمله القائد معه</h2>
                <p class="mt-5 text-lg leading-8 text-[#315e52] dark:text-[#d2e7df]/76">قاعدة ذهبية: أحضر ما ينفعك فعلًا، لا ما يثقل حقيبتك.</p>
            </div>
            <div class="mt-12 grid gap-3 lg:grid-cols-2">
                @foreach ($kitGroups as $index => $group)
                    <details class="group rounded-2xl border border-[#2a8069]/14 bg-white/70 p-5 open:border-[#007a52]/30 dark:border-white/10 dark:bg-white/5 dark:open:border-[#6ee7b7]/24" @if ($index === 0) open @endif>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-heading text-xl font-bold text-[#123329] dark:text-[#f7f1df]">
                            <span>{{ $group['title'] }}</span>
                            <span class="text-2xl text-[#007a52] transition group-open:rotate-45 dark:text-[#6ee7b7] motion-reduce:transition-none">+</span>
                        </summary>
                        <ul class="mt-5 grid gap-3 border-t border-[#2a8069]/12 pt-5 text-sm leading-7 text-[#315e52] dark:border-white/10 dark:text-[#d2e7df]/72">
                            @foreach ($group['items'] as $item)
                                <li class="flex gap-3"><x-hugeicon name="checkmark-circle-01" class="mt-1 shrink-0 text-lg text-[#007a52] dark:text-[#6ee7b7]" /><span>{{ $item }}</span></li>
                            @endforeach
                        </ul>
                    </details>
                @endforeach
            </div>
        </section>

        <section class="border-y border-[#2a8069]/12 bg-white/56 dark:border-white/10 dark:bg-white/[0.03]">
            <div class="mx-auto w-full max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
                <h2 class="max-w-3xl font-heading text-4xl font-bold text-[#123329] lg:text-6xl dark:text-[#f7f1df]">أجوبة صريحة لأسئلة أولياء الأمور</h2>
                <div class="mt-12 columns-1 gap-4 lg:columns-2">
                    @foreach ($faqs as $faq)
                        <details class="group mb-4 break-inside-avoid rounded-2xl border border-[#2a8069]/14 bg-white/72 p-5 open:border-[#007a52]/30 dark:border-white/10 dark:bg-white/5 dark:open:border-[#6ee7b7]/24">
                            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-heading text-xl font-bold text-[#123329] dark:text-[#f7f1df]">
                                <span>{{ $faq['question'] }}</span>
                                <span class="text-2xl text-[#007a52] transition group-open:rotate-45 dark:text-[#6ee7b7] motion-reduce:transition-none">+</span>
                            </summary>
                            <p class="mt-4 border-t border-[#2a8069]/12 pt-4 leading-8 text-[#315e52] dark:border-white/10 dark:text-[#d2e7df]/74">{{ $faq['answer'] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
            <div class="relative overflow-hidden rounded-2xl border border-[#007a52]/12 bg-[#e5f6ef] p-8 dark:border-[#6ee7b7]/12 dark:bg-[#6ee7b7]/7 sm:p-12 lg:p-16">
                <div class="pointer-events-none absolute -end-24 -top-24 size-72 rounded-full bg-[#e0a800]/12 blur-3xl"></div>
                <div class="relative flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                    <div class="max-w-3xl">
                        <h2 class="font-heading text-4xl font-bold leading-tight text-[#123329] lg:text-5xl dark:text-[#f7f1df]">مقاعد الباقة الأولى أرخصها وأقلها</h2>
                        <p class="mt-5 text-lg leading-8 text-[#315e52] dark:text-[#d2e7df]/76">بادر قبل أن تمتلئ. اختر الابن، راجع الباقة المفتوحة وخطة السداد، ثم ثبّت المقعد بالدفع من النظام نفسه.</p>
                    </div>
                    <x-public-event-interest-action :event="$event" button-class="inline-flex min-h-14 shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-full bg-[#007a52] px-8 py-4 font-bold text-white transition hover:-translate-y-0.5 hover:bg-[#006746] active:translate-y-px motion-reduce:transition-none motion-reduce:hover:translate-y-0" />
                </div>
            </div>
        </section>
    </article>
@endsection
