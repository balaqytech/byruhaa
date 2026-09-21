<section id="menu" class="scroll-mt-24 bg-[#f6fbf8] dark:bg-[#07120f]" aria-labelledby="store-menu-title">
    <div class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
        <div class="max-w-3xl">
            <p class="text-sm font-bold text-[#007a52] dark:text-[#6ee7b7]">اطلب مسبقًا واستلم من المكان</p>
            <h2 id="store-menu-title" class="mt-3 font-heading text-3xl font-bold text-[#123329] sm:text-4xl lg:text-5xl dark:text-[#f7f1df]">اختر من قائمتنا</h2>
            <p class="mt-4 max-w-[62ch] leading-8 text-[#315e52] dark:text-[#d2e7df]/76">تصفّح الأصناف المعتمدة، اختر المنتج وخياره، ثم أضفه إلى السلة. الأسعار تشمل ٥٪ ضريبة القيمة المضافة.</p>
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
                                    <span class="text-sm font-bold text-[#007a52] dark:text-[#6ee7b7]"><x-money :amount-baisa="$selectedOption->price_baisa" :currency="$selectedOption->currency" /></span>
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
                                    @if ($product->options->count() > 1)
                                        <label class="grid gap-1.5 text-xs font-bold text-[#315e52] dark:text-[#d2e7df]/78">
                                            إضافة نكهة
                                            <select wire:model.live="selectedOptions.{{ $product->id }}" class="min-h-10 w-full rounded-lg border border-[#2a8069]/18 bg-[#f6fbf8] px-2 text-xs text-[#123329] outline-none focus:border-[#007a52] focus:ring-2 focus:ring-[#007a52]/15 dark:border-white/12 dark:bg-[#0c1e19] dark:text-[#f7f1df]">
                                                @foreach ($product->options as $option)
                                                    <option value="{{ $option->id }}">{{ $option->name }} - {{ \App\Support\MoneyFormatter::baisa($option->price_baisa, $option->currency) }}</option>
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
</section>
