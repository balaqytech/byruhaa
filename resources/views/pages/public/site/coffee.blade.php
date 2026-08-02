@extends('layouts.public', [
    'title' => $title,
    'metaDescription' => $metaDescription,
    'metaImage' => $metaImage,
])

@php
    $whatsappUrl = 'https://wa.me/'.$coffee['whatsapp_number'].'?text='.rawurlencode($coffee['whatsapp_message']);
@endphp

@section('content')
    <section class="relative isolate overflow-hidden">
        <div
            class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_82%_16%,rgba(0,144,96,0.14),transparent_34%),radial-gradient(circle_at_16%_84%,rgba(62,86,72,0.10),transparent_32%)] dark:bg-[radial-gradient(circle_at_82%_16%,rgba(52,211,153,0.10),transparent_34%),radial-gradient(circle_at_16%_84%,rgba(224,168,0,0.06),transparent_32%)]">
        </div>

        <div
            class="mx-auto grid min-h-[calc(100dvh-5rem)] w-full max-w-7xl items-center gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[minmax(0,0.82fr)_minmax(500px,1.18fr)] lg:px-8 lg:py-16">
            <div class="max-w-2xl">
                <p
                    class="inline-flex min-h-10 items-center gap-2 rounded-sm border border-[#009060]/20 bg-white/72 px-3 py-2 text-sm font-bold text-[#007a52] shadow-sm shadow-[#123329]/5 dark:border-[#34d399]/20 dark:bg-white/6 dark:text-[#6ee7b7]">
                    <x-hugeicon name="sparkles" class="text-lg" />
                    قهوة بيرحاء
                </p>

                <h1
                    class="mt-5 max-w-2xl font-heading text-4xl font-bold leading-[1.2] text-[#123329] sm:text-5xl lg:text-6xl dark:text-[#f7f1df]">
                    فنجانٌ هادئ، في مكانه
                </h1>

                <p class="mt-6 max-w-[54ch] text-lg leading-8 text-[#315e52] dark:text-[#d2e7df]/80">
                    قهوة مختصة ومخبوزات خفيفة تُقدّم في مخيم بيرحاء للزائر والفتى وأسرته.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="#menu"
                        class="inline-flex min-h-12 items-center justify-center gap-2 whitespace-nowrap rounded-sm bg-[#007a52] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#007a52]/16 transition hover:-translate-y-0.5 hover:bg-[#006746] active:translate-y-px motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                        <span>تصفّح القائمة</span>
                        <x-hugeicon name="arrow-left-02" class="text-lg" />
                    </a>
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
                        class="inline-flex min-h-12 items-center justify-center gap-2 whitespace-nowrap rounded-sm border border-[#123329]/18 bg-white/68 px-6 py-3 text-sm font-bold text-[#123329] transition hover:-translate-y-0.5 hover:border-[#007a52]/38 hover:text-[#007a52] active:translate-y-px dark:border-white/16 dark:bg-white/5 dark:text-[#f7f1df] dark:hover:border-[#6ee7b7]/36 dark:hover:text-[#6ee7b7] motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                        <span>اسأل عن القهوة</span>
                        <x-hugeicon name="mail-01" class="text-lg" />
                    </a>
                </div>

                <dl class="mt-9 grid gap-5 border-t border-[#2a8069]/16 pt-6 sm:grid-cols-3 dark:border-white/12">
                    <div>
                        <dt class="text-sm text-[#315e52]/70 dark:text-[#d2e7df]/58">البداية</dt>
                        <dd class="mt-1 font-bold text-[#123329] dark:text-[#f7f1df]">{{ $coffee['opening_date'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-[#315e52]/70 dark:text-[#d2e7df]/58">المكان</dt>
                        <dd class="mt-1 font-bold text-[#123329] dark:text-[#f7f1df]">إبراء</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-[#315e52]/70 dark:text-[#d2e7df]/58">الخدمة</dt>
                        <dd class="mt-1 font-bold text-[#123329] dark:text-[#f7f1df]">من الموقع</dd>
                    </div>
                </dl>
            </div>

            <div class="relative mx-auto w-full max-w-3xl lg:mx-0">
                <div
                    class="overflow-hidden rounded-sm border border-[#2a8069]/14 bg-white shadow-[0_32px_90px_rgba(18,51,41,0.16)] dark:border-white/10 dark:bg-white/5 dark:shadow-black/30">
                    <img src="{{ asset('images/coffee-byruha-hero.webp') }}"
                        alt="تحضير قهوة مقطرة في فناء عُماني بمخيم بيرحاء" width="1536" height="1024"
                        fetchpriority="high" class="aspect-[3/2] h-full w-full object-cover">
                </div>
                <p
                    class="relative z-10 mt-4 max-w-md rounded-sm border border-[#2a8069]/14 bg-[#f6fbf8] px-5 py-4 text-sm font-semibold leading-7 text-[#123329] shadow-xl shadow-[#123329]/10 dark:border-white/10 dark:bg-[#0c1e19] dark:text-[#f7f1df] sm:ms-auto">
                    القائمة قصيرة عن قصد، حتى يبقى التحضير جيدًا والخيار واضحًا.
                </p>
            </div>
        </div>
    </section>

    <section class="border-y border-[#2a8069]/12 bg-white/62 dark:border-white/10 dark:bg-white/[0.03]">
        <div
            class="mx-auto grid w-full max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[minmax(0,1.12fr)_minmax(0,0.88fr)] lg:items-center lg:px-8 lg:py-20">
            <div class="overflow-hidden rounded-sm border border-[#2a8069]/14 bg-white dark:border-white/10 dark:bg-white/5">
                <img src="{{ asset('images/coffee-byruha-menu.webp') }}"
                    alt="مجموعة من مشروبات القهوة والمخبوزات المقدمة في قهوة بيرحاء" width="1536" height="1024"
                    loading="lazy" class="aspect-[3/2] h-full w-full object-cover">
            </div>

            <div class="max-w-2xl">
                <h2 class="font-heading text-3xl font-bold leading-tight text-[#123329] lg:text-5xl dark:text-[#f7f1df]">
                    القهوة امتدادٌ لضيافة بيرحاء
                </h2>
                <p class="mt-5 leading-8 text-[#315e52] dark:text-[#d2e7df]/76">
                    ليست متجرًا منفصلًا عن المكان. هي محطة يومية للزائر، ومساحة لقاء خفيفة قبل البرنامج أو بعده، بخيارات معروفة وسعر ظاهر.
                </p>
                <div class="mt-7 grid gap-4 sm:grid-cols-2">
                    <div class="border-t border-[#2a8069]/18 pt-4 dark:border-white/12">
                        <p class="font-heading text-xl font-bold text-[#123329] dark:text-[#f7f1df]">تحضير واضح</p>
                        <p class="mt-2 leading-7 text-[#315e52] dark:text-[#d2e7df]/72">مشروبات أساسية وموسمية بلا قائمة متضخمة.</p>
                    </div>
                    <div class="border-t border-[#2a8069]/18 pt-4 dark:border-white/12">
                        <p class="font-heading text-xl font-bold text-[#123329] dark:text-[#f7f1df]">شراء من المكان</p>
                        <p class="mt-2 leading-7 text-[#315e52] dark:text-[#d2e7df]/72">لا سلة إلكترونية ولا دفع منفصل في هذه المرحلة.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="menu" class="relative scroll-mt-24">
        <div class="mx-auto w-full max-w-7xl px-4 py-18 sm:px-6 lg:px-8 lg:py-24">
            <div class="max-w-3xl">
                <h2 class="font-heading text-3xl font-bold text-[#123329] lg:text-5xl dark:text-[#f7f1df]">القائمة الافتتاحية</h2>
                <p class="mt-4 max-w-[62ch] leading-8 text-[#315e52] dark:text-[#d2e7df]/76">
                    أسعار واضحة بالريال العُماني. قد يتغير توفر بعض الأصناف يوميًا، ويؤكد السعر عند الطلب في الموقع.
                </p>
            </div>

            <div class="mt-10 grid gap-x-12 gap-y-12 lg:grid-cols-2">
                @foreach ($coffee['groups'] as $group)
                    <section aria-labelledby="coffee-group-{{ $group['id'] }}"
                        class="border-t-2 border-[#007a52] pt-5 dark:border-[#6ee7b7]">
                        <h3 id="coffee-group-{{ $group['id'] }}"
                            class="font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">
                            {{ $group['name'] }}
                        </h3>
                        <p class="mt-2 text-sm leading-7 text-[#315e52]/78 dark:text-[#d2e7df]/66">{{ $group['description'] }}</p>

                        <div class="mt-5 grid gap-5">
                            @foreach ($group['items'] as $item)
                                <article class="grid grid-cols-[minmax(0,1fr)_auto] gap-5">
                                    <div>
                                        <h4 class="text-base font-bold text-[#123329] dark:text-[#f7f1df]">{{ $item['name'] }}</h4>
                                        <p class="mt-1 text-sm leading-6 text-[#315e52]/76 dark:text-[#d2e7df]/64">{{ $item['description'] }}</p>
                                    </div>
                                    <p class="whitespace-nowrap font-bold text-[#007a52] dark:text-[#6ee7b7]">
                                        <x-money :amount-baisa="$item['price_baisa']" :currency="$coffee['currency']" />
                                    </p>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </section>

    <section class="border-t border-[#2a8069]/12 bg-white/62 dark:border-white/10 dark:bg-white/[0.03]">
        <div
            class="mx-auto grid w-full max-w-7xl gap-8 px-4 py-14 sm:px-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end lg:px-8 lg:py-16">
            <div class="max-w-3xl">
                <h2 class="font-heading text-3xl font-bold text-[#123329] lg:text-4xl dark:text-[#f7f1df]">زرنا في بيرحاء</h2>
                <p class="mt-4 leading-8 text-[#315e52] dark:text-[#d2e7df]/76">
                    {{ $coffee['location'] }}. {{ $coffee['service_note'] }}، ويمكن طلب الموقع أو الاستفسار عن توفر صنف عبر واتساب.
                </p>
            </div>
            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer"
                class="inline-flex min-h-12 items-center justify-center gap-2 whitespace-nowrap rounded-sm bg-[#007a52] px-6 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-[#006746] active:translate-y-px motion-reduce:transition-none motion-reduce:hover:translate-y-0">
                <span>اطلب الموقع</span>
                <x-hugeicon name="map-pin" class="text-lg" />
            </a>
        </div>
    </section>
@endsection
