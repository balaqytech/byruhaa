<div wire:keydown.escape.window="closeCart" class="contents">
    @if ($itemCount > 0)
        <button type="button" wire:click="openCart" wire:loading.attr="disabled" aria-controls="store-cart-drawer" aria-expanded="{{ $cartOpen ? 'true' : 'false' }}" aria-label="فتح السلة، {{ $itemCount }} منتجات" class="fixed bottom-24 start-4 z-50 inline-flex min-h-14 items-center gap-3 rounded-full bg-[#007a52] px-4 py-3 text-sm font-bold text-white shadow-xl shadow-[#007a52]/25 transition hover:-translate-y-0.5 hover:bg-[#006746] focus:outline-none focus-visible:ring-4 focus-visible:ring-[#007a52]/25 lg:bottom-6 lg:start-6">
            <span class="relative grid size-9 place-items-center rounded-full bg-white/15">
                <x-hugeicon name="wallet-02" class="text-xl" />
                <span class="absolute -right-1 -top-1 grid min-w-5 place-items-center rounded-full bg-[#f0c96a] px-1 text-[10px] font-black leading-5 text-[#123329]">{{ $itemCount }}</span>
            </span>
            <span>عرض السلة</span>
        </button>
    @endif

    @if ($cartOpen && $cart?->items->isNotEmpty())
        <div class="fixed inset-0 z-[70]" role="dialog" aria-modal="true" aria-labelledby="store-cart-drawer-title">
            <button type="button" wire:click="closeCart" class="absolute inset-0 bg-[#07120f]/50 backdrop-blur-[2px]" aria-label="إغلاق السلة"></button>
            <aside id="store-cart-drawer" class="absolute inset-x-4 bottom-4 max-h-[calc(100dvh-7rem)] overflow-y-auto rounded-2xl border border-[#2a8069]/16 bg-white p-5 shadow-2xl dark:border-white/10 dark:bg-[#0c1e19] sm:inset-x-auto sm:start-6 sm:top-24 sm:bottom-auto sm:w-[min(26rem,calc(100vw-3rem))]" aria-labelledby="store-cart-drawer-title">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#b07c00]">مراجعة الطلب</p>
                        <h2 id="store-cart-drawer-title" class="mt-1 font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">سلتك</h2>
                    </div>
                    <span class="rounded-full bg-[#e9f7f0] px-2.5 py-1 text-xs font-bold text-[#006746] dark:bg-[#0c2a20] dark:text-[#a7f3d0]">{{ $itemCount }}</span>
                </div>

                @if ($cartError)
                    <div role="alert" class="mt-4 rounded-xl border border-[#b45309]/25 bg-[#fff8e7] px-3 py-2 text-xs font-semibold text-[#92400e] dark:border-[#f0c96a]/20 dark:bg-[#2d2410] dark:text-[#f9d98b]">{{ $cartError }}</div>
                @endif
                @if ($errors->any())
                    <div role="alert" class="mt-4 rounded-xl border border-red-600/20 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 dark:border-red-300/20 dark:bg-red-950/30 dark:text-red-200">{{ $errors->first() }}</div>
                @endif

                <div class="mt-5 grid gap-4">
                    @foreach ($cart->items as $item)
                        <div wire:key="floating-cart-item-{{ $item->id }}" class="border-b border-[#2a8069]/12 pb-4 last:border-0 dark:border-white/10">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0"><p class="truncate font-bold text-[#123329] dark:text-[#f7f1df]">{{ $item->productOption->product->name }}</p><p class="truncate text-xs text-[#315e52] dark:text-[#d2e7df]/65">{{ $item->productOption->name }}</p></div>
                                <button type="button" wire:click="removeItem({{ $item->id }})" aria-label="إزالة {{ $item->productOption->product->name }}" class="shrink-0 text-xs font-bold text-red-700 hover:underline dark:text-red-300">إزالة</button>
                            </div>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <div class="inline-flex items-center rounded-xl border border-[#2a8069]/16 dark:border-white/12">
                                    <button type="button" wire:click="updateItem({{ $item->id }}, {{ max(1, $item->quantity - 1) }})" class="size-9 text-lg" aria-label="إنقاص الكمية">−</button>
                                    <span class="min-w-8 text-center text-sm font-bold">{{ $item->quantity }}</span>
                                    <button type="button" wire:click="updateItem({{ $item->id }}, {{ min(99, $item->quantity + 1) }})" class="size-9 text-lg" aria-label="زيادة الكمية">+</button>
                                </div>
                                <p class="text-sm font-bold text-[#007a52] dark:text-[#6ee7b7]"><x-money :amount-baisa="$item->productOption->price_baisa * $item->quantity" :currency="$item->productOption->currency" /></p>
                            </div>
                            <label class="mt-3 block text-xs font-semibold text-[#315e52] dark:text-[#d2e7df]/70">ملاحظة
                                <input type="text" value="{{ $item->note }}" wire:blur="updateNote({{ $item->id }}, $event.target.value)" maxlength="5000" class="mt-1 min-h-10 w-full rounded-xl border border-[#2a8069]/16 bg-transparent px-3 text-sm focus:border-[#007a52] focus:outline-none focus:ring-2 focus:ring-[#007a52]/15 dark:border-white/12">
                            </label>
                        </div>
                    @endforeach
                </div>

                @if ($quote)
                    <dl class="mt-5 grid gap-2 border-t border-[#2a8069]/12 pt-4 text-sm dark:border-white/10">
                        <div class="flex justify-between"><dt>المجموع</dt><dd class="font-bold"><x-money :amount-baisa="$quote['subtotal_baisa']" currency="OMR" /></dd></div>
                        <div class="flex justify-between text-[#315e52] dark:text-[#d2e7df]/65"><dt>ضريبة القيمة المضافة</dt><dd><x-money :amount-baisa="$quote['vat_baisa']" currency="OMR" /></dd></div>
                        <div class="flex justify-between border-t border-[#2a8069]/12 pt-3 text-base font-bold dark:border-white/10"><dt>الإجمالي</dt><dd class="text-[#007a52] dark:text-[#6ee7b7]"><x-money :amount-baisa="$quote['total_baisa']" currency="OMR" /></dd></div>
                    </dl>
                @endif

                <a href="{{ route('store.checkout') }}" class="mt-5 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-[#007a52] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#006746]">إتمام الطلب والدفع <x-hugeicon name="arrow-left-02" class="text-lg" /></a>
            </aside>
        </div>
    @endif
</div>
