<section id="menu" class="scroll-mt-24 bg-[#f6fbf8] dark:bg-[#07120f]" aria-labelledby="store-menu-title">
    <div class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
        <div class="max-w-3xl">
            <p class="text-sm font-bold text-[#007a52] dark:text-[#6ee7b7]">اطلب مسبقًا واستلم من المكان</p>
            <h2 id="store-menu-title" class="mt-3 font-heading text-3xl font-bold text-[#123329] sm:text-4xl lg:text-5xl dark:text-[#f7f1df]">اختر من قائمتنا</h2>
            <p class="mt-4 max-w-[62ch] leading-8 text-[#315e52] dark:text-[#d2e7df]/76">تصفّح الأصناف المعتمدة، اختر المنتج وخياره، ثم أضفه إلى السلة. الأسعار تشمل ٥٪ ضريبة القيمة المضافة.</p>
            @if ($hasMemberOffers && ! $memberPricingEligible)
                <p class="mt-3 text-sm font-semibold text-[#6f5420] dark:text-[#ead8ac]">لديك حساب في بيرحاء؟ <a href="{{ route('login') }}" class="text-[#007a52] underline underline-offset-4 dark:text-[#6ee7b7]">سجّل الدخول لتحصل على سعر الأعضاء تلقائيًا</a>.</p>
            @endif
        </div>

        @if (! $orderingEnabled)
            <div role="status" class="mt-6 rounded-xl border border-[#b07c00]/25 bg-[#fff8e7] px-4 py-3 text-sm font-semibold text-[#92400e] dark:border-[#f0c96a]/20 dark:bg-[#2d2410] dark:text-[#f9d98b]">الطلب المسبق غير متاح حاليًا. يمكنك تصفّح القائمة أو التواصل معنا لمعرفة المتاح.</div>
        @endif

        @if ($feedback)
            <div role="status" class="mt-6 rounded-xl border border-[#007a52]/20 bg-[#e9f7f0] px-4 py-3 text-sm font-semibold text-[#006746] dark:border-[#6ee7b7]/20 dark:bg-[#0c2a20] dark:text-[#a7f3d0]">{{ $feedback }}</div>
        @endif

        @if ($cartError)
            <div role="alert" class="mt-6 rounded-xl border border-[#b45309]/25 bg-[#fff8e7] px-4 py-3 text-sm font-semibold text-[#92400e] dark:border-[#f0c96a]/20 dark:bg-[#2d2410] dark:text-[#f9d98b]">{{ $cartError }}</div>
        @endif

        @if ($errors->any())
            <div role="alert" class="mt-6 rounded-xl border border-red-600/20 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 dark:border-red-300/20 dark:bg-red-950/30 dark:text-red-200">{{ $errors->first() }}</div>
        @endif

        @if ($catalog->isEmpty())
            <div class="mt-12 border-y border-[#2a8069]/16 py-14 text-center dark:border-white/10">
                <x-hugeicon name="sparkles" class="mx-auto text-4xl text-[#007a52] dark:text-[#6ee7b7]" />
                <h3 class="mt-4 font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">القائمة غير متاحة مؤقتًا</h3>
                <p class="mt-3 text-[#315e52] dark:text-[#d2e7df]/70">نعيد ترتيب القائمة الآن. تواصل معنا عبر واتساب لمعرفة المتاح اليوم.</p>
            </div>
        @else
            <div class="mt-9">
                <div class="grid snap-x snap-mandatory grid-flow-col auto-cols-[6rem] gap-3 overflow-x-auto px-1 pb-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden lg:auto-cols-fr lg:grid-flow-row lg:overflow-visible {{ $hasFeaturedProducts ? 'lg:grid-cols-9' : 'lg:grid-cols-8' }}" role="group" aria-label="تصنيفات القائمة">
                    @if ($hasFeaturedProducts)
                        <button type="button" aria-pressed="{{ $featuredOnly ? 'true' : 'false' }}" wire:click="selectFeatured" class="group snap-start text-start focus:outline-none">
                            <span class="relative block aspect-[9/14] overflow-hidden rounded-xl bg-[#123329] ring-offset-2 ring-offset-[#f6fbf8] transition duration-200 group-hover:-translate-y-0.5 group-focus-visible:ring-2 group-focus-visible:ring-[#007a52] dark:ring-offset-[#07120f] {{ $featuredOnly ? 'ring-2 ring-[#d2a53a]' : 'ring-1 ring-[#2a8069]/14 dark:ring-white/10' }}">
                                <img src="{{ asset('images/coffee-byruha-menu.webp') }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" />
                                <span class="absolute inset-0 bg-gradient-to-t from-[#07120f]/95 via-[#07120f]/35 to-transparent"></span>
                                <span class="absolute inset-x-0 bottom-0 p-2 text-white"><strong class="block text-xs leading-5">مختاراتنا</strong></span>
                            </span>
                        </button>
                    @endif
                    @foreach ($catalog as $category)
                        @php
                            $visual = $categoryVisuals[$category->slug] ?? ['image' => null, 'from' => '#0E7C7B', 'to' => '#16263F'];
                        @endphp
                        <button
                            type="button"
                            aria-pressed="{{ $categoryId === $category->id ? 'true' : 'false' }}"
                            wire:key="category-{{ $category->id }}"
                            wire:click="selectCategory({{ $category->id }})"
                            class="group snap-start text-start focus:outline-none"
                        >
                            <span class="relative block aspect-[9/14] overflow-hidden rounded-xl bg-[#123329] ring-offset-2 ring-offset-[#f6fbf8] transition duration-200 group-hover:-translate-y-0.5 group-focus-visible:ring-2 group-focus-visible:ring-[#007a52] dark:ring-offset-[#07120f] {{ $categoryId === $category->id ? 'ring-2 ring-[#d2a53a]' : 'ring-1 ring-[#2a8069]/14 dark:ring-white/10' }}">
                                @if ($visual['image'])
                                    <img src="{{ $visual['image'] }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" />
                                @endif
                                <span class="absolute inset-0 bg-gradient-to-t from-[#07120f]/95 via-[#07120f]/35 to-transparent"></span>
                                <span class="absolute inset-x-0 bottom-0 p-2 text-white">
                                    <strong class="block text-xs leading-5">{{ $category->short_name ?? $category->name }}</strong>
                                    <small class="text-[0.68rem] text-white/72">{{ $category->products->count() }} صنفًا</small>
                                </span>
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            @foreach ($displayCatalog as $category)
                <div class="mt-8" wire:key="category-panel-{{ $category->id }}">
                    <h3 class="font-heading text-2xl font-bold text-[#123329] sm:text-3xl dark:text-[#f7f1df]">{{ $category->name }}</h3>
                    @if (filled($category->description))
                        <p class="mt-2 max-w-[60ch] leading-7 text-[#315e52] dark:text-[#d2e7df]/72">{{ $category->description }}</p>
                    @endif
                </div>
            @endforeach

            <div wire:loading.flex wire:target="selectCategory" class="mt-8 grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-5 xl:grid-cols-4" aria-label="جارٍ تحديث المنتجات">
                @foreach (range(1, 4) as $placeholder)
                    <div class="overflow-hidden rounded-xl border border-[#2a8069]/12 bg-white dark:border-white/10 dark:bg-white/5" aria-hidden="true">
                        <div class="aspect-[9/12] animate-pulse bg-[#dfeee8] motion-reduce:animate-none dark:bg-white/10"></div>
                        <div class="grid gap-3 p-3 sm:p-4"><div class="h-5 w-2/3 animate-pulse rounded bg-[#dfeee8] motion-reduce:animate-none dark:bg-white/10"></div><div class="h-4 w-full animate-pulse rounded bg-[#dfeee8] motion-reduce:animate-none dark:bg-white/10"></div><div class="h-11 w-full animate-pulse rounded bg-[#dfeee8] motion-reduce:animate-none dark:bg-white/10"></div></div>
                    </div>
                @endforeach
            </div>

            <div id="store-products" wire:loading.remove wire:target="selectCategory" class="mt-8 grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-5 xl:grid-cols-4" aria-live="polite">
                @foreach ($displayCatalog as $category)
                    @php
                        $visual = $categoryVisuals[$category->slug] ?? ['image' => null, 'from' => '#0E7C7B', 'to' => '#16263F'];
                        $showsAllergenInfo = in_array($category->slug, ['fresh', 'frozen', 'sweets', 'cold', 'hot'], true);
                    @endphp

                    @foreach ($category->products as $product)
                        @php
                            $selectedOption = $product->options->firstWhere('id', (int) ($selectedOptions[$product->id] ?? 0))
                                ?? $product->options->firstWhere('is_default', true)
                                ?? $product->options->first();
                            $featuredImageUrl = $product->featuredImage?->getUrl();
                        @endphp

                        <article wire:key="product-{{ $product->id }}" class="flex min-w-0 flex-col overflow-hidden rounded-xl border border-[#2a8069]/14 bg-white transition duration-200 hover:-translate-y-0.5 hover:border-[#007a52]/30 hover:shadow-[0_18px_45px_rgba(18,51,41,0.1)] dark:border-white/10 dark:bg-white/5 dark:hover:shadow-black/25 motion-reduce:transform-none motion-reduce:transition-none">
                            <div class="relative aspect-[9/12] overflow-hidden" style="background: linear-gradient(160deg, {{ $visual['from'] }}, {{ $visual['to'] }});">
                                @if ($featuredImageUrl)
                                    <img src="{{ $featuredImageUrl }}" alt="{{ $product->name }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover" />
                                @else
                                    <span class="sr-only">لا توجد صورة متاحة للمنتج: {{ $product->name }}</span>
                                    <span aria-hidden="true" class="absolute inset-0 grid place-items-center px-4 text-center font-heading text-xl font-bold text-white/14 sm:text-2xl">{{ $category->short_name ?? $category->name }}</span>
                                @endif
                                <span class="absolute inset-0 bg-gradient-to-t from-[#07120f]/95 via-[#07120f]/36 to-transparent"></span>
                                <div class="absolute inset-x-0 bottom-0 p-3 text-white sm:p-4">
                                    <h4 class="font-heading text-base font-bold leading-6 sm:text-lg">{{ $product->name }}</h4>
                                    @if (filled($product->source_name))
                                        <p class="mt-0.5 text-xs text-white/76">({{ $product->source_name }})</p>
                                    @endif
                                    @if (filled($product->author_name))
                                        <p class="mt-0.5 text-xs text-white/76">{{ $product->author_name }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-1 flex-col gap-3 p-3 sm:p-4">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-bold text-[#8a6420] dark:text-[#f0c96a]">{{ $product->display_tag }}</span>
                                    <span class="text-end text-sm font-bold text-[#007a52] dark:text-[#6ee7b7]">
                                        @if ($selectedOption->member_price_baisa !== null)
                                            <span class="block text-[0.68rem] font-medium text-[#315e52]/65 line-through dark:text-[#d2e7df]/55"><x-money :amount-baisa="$selectedOption->price_baisa" :currency="$selectedOption->currency" /></span>
                                            <span class="block"><x-money :amount-baisa="$selectedOption->member_price_baisa" :currency="$selectedOption->currency" /> <small class="font-semibold">للأعضاء</small></span>
                                        @else
                                            <x-money :amount-baisa="$selectedOption->price_baisa" :currency="$selectedOption->currency" />
                                        @endif
                                    </span>
                                </div>

                                <p class="text-sm leading-6 text-[#6f5420] dark:text-[#ead8ac]">{{ $product->description }}</p>

                                @if ($showsAllergenInfo)
                                    <div class="flex flex-wrap gap-1" aria-label="مسبّبات الحساسية">
                                        @forelse ($product->allergens ?? [] as $allergen)
                                            <span class="rounded border border-[#2a8069]/14 px-1.5 py-0.5 text-[0.68rem] text-[#315e52] dark:border-white/12 dark:text-[#d2e7df]/72">{{ $allergen }}</span>
                                        @empty
                                            <span class="rounded border border-[#2a8069]/14 px-1.5 py-0.5 text-[0.68rem] text-[#315e52] dark:border-white/12 dark:text-[#d2e7df]/72">بلا مسبّبات شائعة</span>
                                        @endforelse
                                    </div>
                                @endif

                                <div class="mt-auto grid gap-3 pt-1">
                                    <button type="button" wire:click="openProductDetails({{ $product->id }})" aria-label="اعرض تفاصيل {{ $product->name }}" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-[#2a8069]/20 px-3 py-2 text-xs font-bold text-[#007a52] transition hover:bg-[#e9f7f0] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#d2a53a] sm:text-sm dark:border-white/15 dark:text-[#6ee7b7] dark:hover:bg-white/10">
                                        تفاصيل المنتج
                                        <x-hugeicon name="arrow-left-02" class="text-base" />
                                    </button>
                                    @if ($product->options->count() > 1)
                                        <label class="grid gap-1.5 text-xs font-bold text-[#315e52] dark:text-[#d2e7df]/78">
                                            إضافة نكهة
                                            <select wire:model.live="selectedOptions.{{ $product->id }}" class="min-h-10 w-full rounded-lg border border-[#2a8069]/18 bg-[#f6fbf8] px-2 text-xs text-[#123329] outline-none focus:border-[#007a52] focus:ring-2 focus:ring-[#007a52]/15 dark:border-white/12 dark:bg-[#0c1e19] dark:text-[#f7f1df]">
                                                @foreach ($product->options as $option)
                                                    <option value="{{ $option->id }}">{{ $option->name }} - {{ \App\Support\MoneyFormatter::baisa($option->member_price_baisa !== null ? ($memberPricingEligible ? $option->member_price_baisa : $option->price_baisa) : $option->price_baisa, $option->currency) }}{{ $option->member_price_baisa !== null && ! $memberPricingEligible ? ' (للأعضاء '.\App\Support\MoneyFormatter::baisa($option->member_price_baisa, $option->currency).')' : '' }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                    @endif

                                    <button type="button" wire:click="addToCart({{ $selectedOption->id }})" wire:loading.attr="disabled" wire:target="addToCart({{ $selectedOption->id }})" @disabled(! $orderingEnabled) aria-label="أضف {{ $product->name }}، خيار {{ $selectedOption->name }} إلى السلة" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-lg bg-[#007a52] px-3 py-2 text-xs font-bold text-white transition hover:bg-[#006746] active:translate-y-px disabled:cursor-not-allowed disabled:bg-[#315e52]/45 disabled:opacity-70 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#d2a53a] sm:text-sm motion-reduce:transform-none motion-reduce:transition-none">
                                        <span wire:loading.remove wire:target="addToCart({{ $selectedOption->id }})" class="inline-flex items-center gap-2"><x-hugeicon name="add-01" class="text-lg" /> {{ $orderingEnabled ? 'أضف إلى السلة' : 'متوقف مؤقتًا' }}</span>
                                        <span wire:loading wire:target="addToCart({{ $selectedOption->id }})">جارٍ الإضافة</span>
                                    </button>
                                </div>
                            </div>
                        </article>
                    @endforeach
                @endforeach
            </div>
        @endif
    </div>

    @if ($detailsProduct)
        @php
            $detailsSelectedOption = $detailsProduct->options->firstWhere('id', (int) ($selectedOptions[$detailsProduct->id] ?? 0))
                ?? $detailsProduct->options->firstWhere('is_default', true)
                ?? $detailsProduct->options->first();
        @endphp
        <div class="fixed inset-0 z-[80]" wire:keydown.escape.window="closeProductDetails">
            <div class="absolute inset-0 bg-[#07120f]/70 backdrop-blur-sm" wire:click="closeProductDetails" aria-hidden="true"></div>
            <aside role="dialog" aria-modal="true" aria-labelledby="product-details-title" dir="rtl" class="absolute inset-y-0 right-0 flex w-full max-w-xl flex-col overflow-hidden bg-[#f6fbf8] shadow-2xl sm:rounded-l-2xl dark:bg-[#0c1e19]">
                <div class="z-10 flex shrink-0 items-center justify-between gap-4 border-b border-[#2a8069]/14 bg-[#f6fbf8] px-4 py-3 dark:border-white/10 dark:bg-[#0c1e19] sm:px-8 sm:py-4">
                    <span class="text-xs font-bold text-[#007a52] dark:text-[#6ee7b7]">{{ $detailsProduct->category->name }}</span>
                    <button type="button" wire:click="closeProductDetails" aria-label="إغلاق تفاصيل المنتج" class="inline-flex size-11 items-center justify-center rounded-full border border-[#2a8069]/16 text-[#123329] hover:bg-[#e9f7f0] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#d2a53a] dark:border-white/15 dark:text-white dark:hover:bg-white/10">✕</button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain">
                    @if ($detailsProduct->featuredImage?->getUrl())
                        <img src="{{ $detailsProduct->featuredImage->getUrl() }}" alt="{{ $detailsProduct->name }}" class="h-44 w-full object-cover sm:h-64" />
                    @endif

                <div class="grid gap-6 px-4 py-6 pb-8 sm:px-8 sm:py-8">
                    <div>
                        <p class="text-xs font-semibold text-[#8a6420] dark:text-[#f0c96a]">{{ $detailsProduct->display_tag }} · {{ $detailsProduct->options->first()?->sku }}</p>
                        <h2 id="product-details-title" class="mt-2 font-heading text-3xl font-bold text-[#123329] dark:text-[#f7f1df]">{{ $detailsProduct->name }}</h2>
                        @if ($detailsProduct->source_name)
                            <p class="mt-1 text-sm text-[#315e52] dark:text-[#d2e7df]/75">{{ $detailsProduct->source_name }}</p>
                        @endif
                        @if ($detailsProduct->author_name)
                            <p class="mt-1 text-sm text-[#315e52] dark:text-[#d2e7df]/75">{{ $detailsProduct->author_name }}</p>
                        @endif
                        @if ($detailsProduct->description)
                            <p class="mt-5 border-r-2 border-[#d2a53a] pr-4 text-base leading-8 text-[#315e52] dark:text-[#d2e7df]">{{ $detailsProduct->description }}</p>
                        @endif
                    </div>

                    @if ($detailsProduct->long_description)
                        <div class="prose prose-emerald max-w-none prose-headings:font-heading prose-headings:text-[#123329] prose-p:leading-8 prose-p:text-[#315e52] dark:prose-invert dark:prose-headings:text-[#f7f1df] dark:prose-p:text-[#d2e7df]">
                            {!! $detailsProduct->renderRichContent('long_description') !!}
                        </div>
                    @endif

                    <section class="rounded-xl border border-[#2a8069]/14 bg-white/70 p-4 dark:border-white/10 dark:bg-white/5" aria-labelledby="product-allergens-title">
                        <h3 id="product-allergens-title" class="text-sm font-bold text-[#123329] dark:text-[#f7f1df]">مسبّبات الحساسية</h3>
                        @if (count($detailsProduct->allergens ?? []) > 0)
                            <ul class="mt-3 flex flex-wrap gap-2" aria-label="مسبّبات الحساسية المدرجة">
                                @foreach ($detailsProduct->allergens as $allergen)
                                    <li class="rounded-full border border-[#b07c00]/25 bg-[#fff8e7] px-3 py-1 text-xs font-semibold text-[#6f5420] dark:border-[#f0c96a]/20 dark:bg-[#2d2410] dark:text-[#f9d98b]">{{ $allergen }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mt-2 text-sm leading-6 text-[#315e52] dark:text-[#d2e7df]/75">لا توجد مسبّبات حساسية مدرجة لهذا المنتج.</p>
                        @endif
                    </section>

                    @if ($detailsProduct->options->count() > 1)
                        <section class="border-t border-[#2a8069]/14 pt-5 dark:border-white/10" aria-labelledby="product-options-title">
                            <h3 id="product-options-title" class="font-heading text-lg font-bold text-[#123329] dark:text-[#f7f1df]">خيارات المنتج</h3>
                            <div class="mt-3 overflow-x-auto rounded-xl border border-[#2a8069]/14 dark:border-white/10">
                                <table class="w-full text-right text-xs sm:text-sm">
                                    <thead class="bg-[#e9f7f0] text-xs font-bold text-[#123329] dark:bg-white/10 dark:text-[#f7f1df]">
                                        <tr>
                                            <th scope="col" class="px-2 py-3 sm:px-3">الخيار</th>
                                            <th scope="col" class="hidden px-3 py-3 sm:table-cell">الرمز</th>
                                            <th scope="col" class="px-2 py-3 sm:px-3">سعر الزائر</th>
                                            <th scope="col" class="px-2 py-3 sm:px-3">سعر العضو</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#2a8069]/12 bg-white text-[#315e52] dark:divide-white/10 dark:bg-white/5 dark:text-[#d2e7df]">
                                        @foreach ($detailsProduct->options as $option)
                                            <tr wire:key="product-details-option-{{ $option->id }}" class="{{ $detailsSelectedOption?->id === $option->id ? 'bg-[#e9f7f0] dark:bg-[#6ee7b7]/10' : '' }}">
                                                <th scope="row" class="px-2 py-3 font-semibold text-[#123329] dark:text-[#f7f1df] sm:px-3">{{ $option->name }}</th>
                                                <td class="hidden px-3 py-3 font-mono text-xs sm:table-cell" dir="ltr">{{ $option->sku }}</td>
                                                <td class="whitespace-nowrap px-2 py-3 sm:px-3"><x-money :amount-baisa="$option->price_baisa" :currency="$option->currency" /></td>
                                                <td class="whitespace-nowrap px-2 py-3 font-semibold text-[#007a52] dark:text-[#6ee7b7] sm:px-3">
                                                    @if ($option->member_price_baisa !== null)
                                                        <x-money :amount-baisa="$option->member_price_baisa" :currency="$option->currency" />
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    @elseif ($detailsProduct->options->first())
                        <div class="border-t border-[#2a8069]/14 pt-5 dark:border-white/10">
                            <p class="text-sm text-[#315e52] dark:text-[#d2e7df]/75">سعر الزائر: <strong class="text-[#123329] dark:text-white"><x-money :amount-baisa="$detailsProduct->options->first()->price_baisa" :currency="$detailsProduct->options->first()->currency" /></strong></p>
                            @if ($detailsProduct->options->first()->member_price_baisa !== null)
                                <p class="mt-1 text-sm text-[#315e52] dark:text-[#d2e7df]/75">سعر العضو: <strong class="text-[#007a52] dark:text-[#6ee7b7]"><x-money :amount-baisa="$detailsProduct->options->first()->member_price_baisa" :currency="$detailsProduct->options->first()->currency" /></strong></p>
                            @endif
                        </div>
                    @endif
                </div>
                </div>

                @if ($detailsSelectedOption)
                    <div class="z-10 shrink-0 border-t border-[#2a8069]/14 bg-[#f6fbf8] px-4 py-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] shadow-[0_-12px_30px_rgba(18,51,41,0.08)] dark:border-white/10 dark:bg-[#0c1e19] sm:px-8 sm:py-4">
                        @if ($detailsProduct->options->count() > 1)
                            <label for="drawer-product-option-{{ $detailsProduct->id }}" class="mb-2 block text-xs font-bold text-[#315e52] dark:text-[#d2e7df]">اختر النكهة أو الخيار</label>
                            <select id="drawer-product-option-{{ $detailsProduct->id }}" wire:model.live="selectedOptions.{{ $detailsProduct->id }}" class="min-h-11 w-full rounded-lg border border-[#2a8069]/20 bg-white px-3 text-sm text-[#123329] focus:border-[#007a52] focus:outline-none focus:ring-2 focus:ring-[#007a52]/20 dark:border-white/15 dark:bg-[#123329] dark:text-white">
                                @foreach ($detailsProduct->options as $option)
                                    <option value="{{ $option->id }}">{{ $option->name }} — {{ \App\Support\MoneyFormatter::baisa($option->member_price_baisa !== null && $memberPricingEligible ? $option->member_price_baisa : $option->price_baisa, $option->currency) }}</option>
                                @endforeach
                            </select>
                        @endif
                        <div class="mt-3 flex items-center gap-3 sm:gap-4">
                            <div class="min-w-0 shrink-0 text-sm font-bold text-[#123329] dark:text-[#f7f1df]">
                                <x-money :amount-baisa="$detailsSelectedOption->member_price_baisa !== null && $memberPricingEligible ? $detailsSelectedOption->member_price_baisa : $detailsSelectedOption->price_baisa" :currency="$detailsSelectedOption->currency" />
                                @if ($detailsSelectedOption->member_price_baisa !== null && $memberPricingEligible)
                                    <span class="block text-[0.65rem] font-medium text-[#007a52] dark:text-[#6ee7b7]">سعر العضو</span>
                                @endif
                            </div>
                            <button type="button" wire:click="addToCart({{ $detailsSelectedOption->id }})" wire:loading.attr="disabled" wire:target="addToCart({{ $detailsSelectedOption->id }})" @disabled(! $orderingEnabled) aria-label="أضف {{ $detailsProduct->name }}، خيار {{ $detailsSelectedOption->name }} إلى السلة" class="inline-flex min-h-12 min-w-0 flex-1 items-center justify-center gap-2 rounded-lg bg-[#007a52] px-3 py-2 text-sm font-bold text-white transition hover:bg-[#006746] active:translate-y-px disabled:cursor-not-allowed disabled:bg-[#315e52]/45 disabled:opacity-70 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#d2a53a]">
                                <span wire:loading.remove wire:target="addToCart({{ $detailsSelectedOption->id }})">{{ $orderingEnabled ? 'أضف إلى السلة' : 'الطلب متوقف مؤقتًا' }}</span>
                                <span wire:loading wire:target="addToCart({{ $detailsSelectedOption->id }})">جارٍ الإضافة</span>
                            </button>
                        </div>
                        @if ($feedback)
                            <p role="status" class="mt-2 text-xs font-semibold text-[#007a52] dark:text-[#6ee7b7]">{{ $feedback }}</p>
                        @endif
                        @if ($errors->any())
                            <p role="alert" class="mt-2 text-xs font-semibold text-red-700 dark:text-red-300">{{ $errors->first() }}</p>
                        @endif
                    </div>
                @endif
            </aside>
        </div>
    @endif
</section>
