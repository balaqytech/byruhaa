<section class="bg-[#f6fbf8] pb-20 pt-6 dark:bg-[#07120f] sm:pt-10" aria-labelledby="product-title">
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
        <nav aria-label="مسار التصفح" class="flex flex-wrap items-center gap-2 text-sm text-[#315e52] dark:text-[#d2e7df]/75">
            <a href="{{ route('coffee') }}#menu" class="font-semibold transition hover:text-[#007a52] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#007a52] dark:hover:text-[#6ee7b7]">قائمة القهوة</a>
            <span aria-hidden="true">/</span>
            <span>{{ $product->category->name }}</span>
            <span aria-hidden="true">/</span>
            <span aria-current="page" class="font-semibold text-[#123329] dark:text-[#f7f1df]">{{ $product->name }}</span>
        </nav>

        <div class="mt-7 grid items-start gap-8 lg:grid-cols-[minmax(0,1.15fr)_minmax(21rem,.85fr)] lg:gap-12">
            <div x-data="{ active: 0 }" class="min-w-0">
                <div class="relative aspect-square overflow-hidden rounded-2xl bg-[#123329] sm:aspect-[5/4] lg:aspect-[4/5]">
                    @forelse ($media as $item)
                        <div x-cloak x-show="active === {{ $loop->index }}" x-transition.opacity.duration.200ms class="absolute inset-0">
                            @if ($item['type'] === 'video')
                                <video data-product-main-video controls playsinline preload="metadata" @if ($posterUrl) poster="{{ $posterUrl }}" @endif aria-label="{{ $item['label'] }}" class="size-full object-contain">
                                    <source src="{{ $item['url'] }}" type="{{ $product->video->mime_type }}">
                                    متصفحك لا يدعم تشغيل الفيديو.
                                </video>
                            @else
                                <img src="{{ $item['url'] }}" alt="{{ $item['label'] }}" @if ($loop->first) fetchpriority="high" @else loading="lazy" @endif class="size-full object-cover">
                            @endif
                        </div>
                    @empty
                        <div class="grid size-full place-items-center px-8 text-center">
                            <span class="font-heading text-3xl font-bold text-white/75">{{ $product->name }}</span>
                        </div>
                    @endforelse
                </div>

                @if (count($media) > 1)
                    <div class="mt-3 flex snap-x gap-3 overflow-x-auto pb-2" role="group" aria-label="وسائط المنتج">
                        @foreach ($media as $item)
                            <button type="button" x-on:click="document.querySelector('[data-product-main-video]')?.pause(); active = {{ $loop->index }}" :aria-pressed="active === {{ $loop->index }}" aria-label="عرض {{ $item['label'] }}" class="relative size-20 shrink-0 snap-start overflow-hidden rounded-xl border-2 border-transparent bg-[#123329] transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#007a52]" :class="active === {{ $loop->index }} ? 'border-[#007a52] dark:border-[#6ee7b7]' : 'hover:border-[#2a8069]/35 dark:hover:border-white/35'">
                                @if ($item['type'] === 'video')
                                    @if ($posterUrl)
                                        <img src="{{ $posterUrl }}" alt="" loading="lazy" class="size-full object-cover opacity-55">
                                    @endif
                                    <span class="absolute inset-0 grid place-items-center text-xs font-bold text-white">فيديو</span>
                                @else
                                    <img src="{{ $item['url'] }}" alt="" loading="lazy" class="size-full object-cover">
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="min-w-0 lg:sticky lg:top-28">
                <p class="text-sm font-bold text-[#007a52] dark:text-[#6ee7b7]">{{ $product->category->name }}</p>
                <h1 id="product-title" class="mt-2 font-heading text-4xl font-black leading-tight text-[#123329] sm:text-5xl dark:text-[#f7f1df]">{{ $product->name }}</h1>
                @if (filled($product->source_name) || filled($product->author_name))
                    <p class="mt-2 text-sm text-[#315e52]/80 dark:text-[#d2e7df]/70">{{ collect([$product->source_name, $product->author_name])->filter()->join(' | ') }}</p>
                @endif
                @if (filled($product->description))
                    <p class="mt-5 max-w-[54ch] text-base leading-8 text-[#315e52] dark:text-[#d2e7df]">{{ $product->description }}</p>
                @endif

                @if ($selectedOption)
                    @php($selectedPrice = $prices->get($selectedOption->id))
                    <div class="mt-7 border-y border-[#2a8069]/16 py-5 dark:border-white/12">
                        <p class="text-sm font-semibold text-[#315e52] dark:text-[#d2e7df]/75">السعر شامل الضريبة</p>
                        <div class="mt-2 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                            <strong class="text-3xl font-black text-[#123329] dark:text-[#f7f1df]"><x-money :amount-baisa="$selectedPrice->effectivePriceBaisa" :currency="$selectedOption->currency" /></strong>
                            @if ($selectedPrice->unitDiscountBaisa > 0)
                                <span class="text-sm text-[#315e52]/70 line-through dark:text-[#d2e7df]/60"><x-money :amount-baisa="$selectedPrice->regularPriceBaisa" :currency="$selectedOption->currency" /></span>
                                <span class="text-xs font-bold text-[#007a52] dark:text-[#6ee7b7]">سعر العضو</span>
                            @elseif ($selectedOption->member_price_baisa !== null && ! $memberPricingEligible)
                                <span class="text-xs font-semibold text-[#315e52] dark:text-[#d2e7df]/75">للأعضاء <x-money :amount-baisa="$selectedOption->member_price_baisa" :currency="$selectedOption->currency" /></span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-6">
                        <h2 class="font-heading text-lg font-bold text-[#123329] dark:text-[#f7f1df]">اختر الخيار</h2>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            @foreach ($product->options as $option)
                                @php($optionPrice = $prices->get($option->id))
                                <label wire:key="product-option-{{ $option->id }}" class="flex cursor-pointer items-center gap-3 rounded-xl border px-4 py-3 transition {{ $selectedOption->id === $option->id ? 'border-[#007a52] bg-[#e9f7f0] dark:border-[#6ee7b7] dark:bg-[#6ee7b7]/10' : 'border-[#2a8069]/16 bg-white hover:border-[#007a52]/45 dark:border-white/12 dark:bg-white/5 dark:hover:border-[#6ee7b7]/45' }}">
                                    <input type="radio" wire:model.live="selectedOptionId" value="{{ $option->id }}" class="size-4 accent-[#007a52]" aria-label="{{ $option->name }}">
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-sm font-bold text-[#123329] dark:text-[#f7f1df]">{{ $option->name }}</span>
                                        <span class="mt-0.5 block text-xs text-[#315e52] dark:text-[#d2e7df]/75"><x-money :amount-baisa="$optionPrice->effectivePriceBaisa" :currency="$option->currency" /></span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-6 grid gap-3 sm:grid-cols-[7rem_1fr]">
                        <label class="grid gap-2 text-sm font-bold text-[#315e52] dark:text-[#d2e7df]">
                            الكمية
                            <input type="number" min="1" max="99" wire:model.live.number="quantity" class="min-h-12 w-full rounded-xl border border-[#2a8069]/20 bg-white px-3 text-center text-base text-[#123329] outline-none focus:border-[#007a52] focus:ring-2 focus:ring-[#007a52]/20 dark:border-white/15 dark:bg-[#123329] dark:text-white">
                        </label>
                        <button type="button" wire:click="addToCart" wire:loading.attr="disabled" wire:target="addToCart" @disabled(! $orderingEnabled) class="self-end inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-[#007a52] px-6 py-3 text-sm font-bold text-white transition hover:bg-[#006746] active:translate-y-px disabled:cursor-not-allowed disabled:opacity-55 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#007a52] motion-reduce:transition-none">
                            <x-hugeicon name="add-01" class="text-lg" />
                            <span wire:loading.remove wire:target="addToCart">{{ $orderingEnabled ? 'أضف إلى السلة' : 'الطلب متوقف مؤقتًا' }}</span>
                            <span wire:loading wire:target="addToCart">جارٍ الإضافة</span>
                        </button>
                    </div>
                    @if ($feedback)
                        <p role="status" class="mt-3 text-sm font-semibold text-[#007a52] dark:text-[#6ee7b7]">{{ $feedback }}</p>
                    @endif
                    @if ($errors->any())
                        <p role="alert" class="mt-3 text-sm font-semibold text-red-700 dark:text-red-300">{{ $errors->first() }}</p>
                    @endif
                @endif

                <div class="mt-8 flex items-start gap-3 border-t border-[#2a8069]/16 pt-6 text-sm leading-7 text-[#315e52] dark:border-white/12 dark:text-[#d2e7df]/75">
                    <x-hugeicon name="home-01" class="mt-0.5 shrink-0 text-xl text-[#007a52] dark:text-[#6ee7b7]" />
                    <p>استلام الطلب من قهوة بيرحاء. الأسعار المعروضة شاملة ضريبة القيمة المضافة.</p>
                </div>
            </div>
        </div>

        @if ($product->long_description || count($product->allergens ?? []) > 0)
            <div class="mt-16 grid gap-10 border-t border-[#2a8069]/16 pt-10 dark:border-white/12 lg:grid-cols-[1.4fr_.6fr]">
                @if ($product->long_description)
                    <section aria-labelledby="product-description-title">
                        <h2 id="product-description-title" class="font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">عن المنتج</h2>
                        <div class="prose prose-emerald mt-4 max-w-none prose-headings:font-heading prose-p:leading-8 prose-p:text-[#315e52] dark:prose-invert dark:prose-p:text-[#d2e7df]">
                            {!! $product->renderRichContent('long_description') !!}
                        </div>
                    </section>
                @endif
                @if (count($product->allergens ?? []) > 0)
                    <section aria-labelledby="product-allergens-title">
                        <h2 id="product-allergens-title" class="font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">مسبّبات الحساسية</h2>
                        <ul class="mt-4 flex flex-wrap gap-2">
                            @foreach ($product->allergens as $allergen)
                                <li class="rounded-lg border border-[#2a8069]/18 px-3 py-1.5 text-sm font-semibold text-[#315e52] dark:border-white/15 dark:text-[#d2e7df]">{{ $allergen }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>
        @endif

        <a href="{{ route('coffee') }}#menu" class="mt-12 inline-flex min-h-11 items-center gap-2 text-sm font-bold text-[#007a52] underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#007a52] dark:text-[#6ee7b7]">
            <x-hugeicon name="arrow-right-02" class="text-lg" /> العودة إلى قائمة القهوة
        </a>
    </div>
</section>
