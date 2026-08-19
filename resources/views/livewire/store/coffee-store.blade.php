<section id="menu" class="scroll-mt-24 bg-[#f6fbf8] dark:bg-[#07120f]" aria-labelledby="store-menu-title">
    <div class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-sm font-bold tracking-[0.18em] text-[#b07c00]">اطلب مسبقًا</p>
                <h2 id="store-menu-title" class="mt-3 font-heading text-3xl font-bold text-[#123329] lg:text-5xl dark:text-[#f7f1df]">القائمة الافتتاحية</h2>
                <p class="mt-4 max-w-[62ch] leading-8 text-[#315e52] dark:text-[#d2e7df]/76">اختر ما يناسبك، أضفه إلى السلة، ثم حدّد وقت الاستلام. الأسعار بالريال العُماني وتشمل ضريبة القيمة المضافة في الملخص النهائي.</p>
            </div>
        </div>

        @if (! $orderingEnabled)<div role="status" class="mt-6 rounded-sm border border-[#b07c00]/25 bg-[#fff8e7] px-4 py-3 text-sm font-semibold text-[#92400e] dark:border-[#f0c96a]/20 dark:bg-[#2d2410] dark:text-[#f9d98b]">الطلب المسبق غير متاح حاليًا. يمكنك تصفّح القائمة أو التواصل معنا لمعرفة المتاح.</div>@endif
        @if ($feedback)<div role="status" class="mt-6 rounded-sm border border-[#007a52]/20 bg-[#e9f7f0] px-4 py-3 text-sm font-semibold text-[#006746] dark:border-[#6ee7b7]/20 dark:bg-[#0c2a20] dark:text-[#a7f3d0]">{{ $feedback }}</div>@endif
        @if ($cartError)<div role="alert" class="mt-6 rounded-sm border border-[#b45309]/25 bg-[#fff8e7] px-4 py-3 text-sm font-semibold text-[#92400e] dark:border-[#f0c96a]/20 dark:bg-[#2d2410] dark:text-[#f9d98b]">{{ $cartError }}</div>@endif
        @if ($errors->any())<div role="alert" class="mt-6 rounded-sm border border-red-600/20 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 dark:border-red-300/20 dark:bg-red-950/30 dark:text-red-200">{{ $errors->first() }}</div>@endif

        @if ($catalog->isEmpty())
            <div class="mt-12 rounded-sm border border-[#2a8069]/16 bg-white p-10 text-center shadow-sm dark:border-white/10 dark:bg-white/5"><x-hugeicon name="sparkles" class="mx-auto text-4xl text-[#007a52]" /><h3 class="mt-4 font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">القائمة غير متاحة مؤقتًا</h3><p class="mt-3 text-[#315e52] dark:text-[#d2e7df]/70">نعيد ترتيب القائمة الآن. تواصل معنا عبر واتساب لمعرفة المتاح اليوم.</p></div>
        @else
            <div class="mt-10 flex gap-2 overflow-x-auto pb-2" role="tablist" aria-label="تصنيفات القائمة">
                <button type="button" wire:click="selectCategory(null)" class="shrink-0 rounded-sm px-4 py-2 text-sm font-bold transition {{ $categoryId === null ? 'bg-[#007a52] text-white' : 'bg-white text-[#315e52] dark:bg-white/5 dark:text-[#d2e7df]' }}">الكل</button>
                @foreach ($catalog as $category)
                    <button type="button" wire:key="category-{{ $category->id }}" wire:click="selectCategory({{ $category->id }})" class="shrink-0 rounded-sm px-4 py-2 text-sm font-bold transition {{ $categoryId === $category->id ? 'bg-[#007a52] text-white' : 'bg-white text-[#315e52] dark:bg-white/5 dark:text-[#d2e7df]' }}">{{ $category->name }}</button>
                @endforeach
            </div>
            <div class="mt-10">
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($catalog as $category)
                        @if ($categoryId === null || $categoryId === $category->id)
                        @foreach ($category->products as $product)
                            <article wire:key="product-{{ $product->id }}" class="flex flex-col overflow-hidden rounded-sm border border-[#2a8069]/14 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg dark:border-white/10 dark:bg-white/5 motion-reduce:transition-none">
                                @if ($product->featuredImage?->getUrl())<img src="{{ $product->featuredImage->getUrl() }}" alt="{{ $product->name }}" loading="lazy" class="aspect-[4/3] w-full object-cover">@endif
                                <div class="flex flex-1 flex-col p-5">
                                    <div class="flex items-start justify-between gap-4"><div><h3 class="font-heading text-xl font-bold text-[#123329] dark:text-[#f7f1df]">{{ $product->name }}</h3><p class="mt-2 text-sm leading-6 text-[#315e52] dark:text-[#d2e7df]/70">{{ $product->description }}</p></div></div>
                                    <div class="mt-5 grid gap-2">
                                        @foreach ($product->options as $option)
                                                <div wire:key="option-{{ $option->id }}" class="flex items-center justify-between gap-3 rounded-sm border border-[#2a8069]/12 px-3 py-2.5 dark:border-white/10">
                                                <div class="flex min-w-0 items-center gap-3">@if (! $product->featuredImage && $option->image?->getUrl())<img src="{{ $option->image->getUrl() }}" alt="" loading="lazy" class="size-12 rounded-sm object-cover">@endif<div class="min-w-0"><p class="truncate text-sm font-bold text-[#123329] dark:text-[#f7f1df]">{{ $option->name }}</p><p class="text-sm font-bold text-[#007a52] dark:text-[#6ee7b7]"><x-money :amount-baisa="$option->price_baisa" :currency="$option->currency" /></p></div></div>
                                                <button type="button" wire:click="addToCart({{ $option->id }})" wire:loading.attr="disabled" wire:target="addToCart({{ $option->id }})" class="inline-flex min-h-10 shrink-0 items-center gap-1.5 rounded-sm bg-[#007a52] px-3 py-2 text-xs font-bold text-white transition hover:bg-[#006746] disabled:cursor-wait disabled:opacity-50"><x-hugeicon name="checkmark-circle-01" class="text-base" /> أضف</button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </article>
                        @endforeach
                        @endif
                    @endforeach
                </div>

            </div>
        @endif
    </div>

</section>
