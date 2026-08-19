<aside id="{{ $cartId ?? 'store-cart-panel' }}" class="rounded-2xl border border-[#2a8069]/16 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5" aria-labelledby="{{ $cartId ?? 'store-cart-panel' }}-title">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#b07c00]">مراجعة الطلب</p>
            <h2 id="{{ $cartId ?? 'store-cart-panel' }}-title" class="mt-1 font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">سلتك</h2>
        </div>
        <span class="rounded-full bg-[#e9f7f0] px-2.5 py-1 text-xs font-bold text-[#006746] dark:bg-[#0c2a20] dark:text-[#a7f3d0]">{{ $cart?->items->sum('quantity') ?? 0 }}</span>
    </div>

    @if (! $cart || $cart->items->isEmpty())
        <div class="py-12 text-center">
            <x-hugeicon name="wallet-02" class="mx-auto text-4xl text-[#007a52]/60" />
            <p class="mt-4 text-sm leading-7 text-[#315e52] dark:text-[#d2e7df]/70">السلة فارغة. أضف مشروبك المفضل لتبدأ.</p>
        </div>
    @else
        <div class="mt-5 grid gap-4">
            @foreach ($cart->items as $item)
                <div wire:key="{{ $keyPrefix ?? 'cart' }}-item-{{ $item->id }}" class="border-b border-[#2a8069]/12 pb-4 last:border-0 dark:border-white/10">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-bold text-[#123329] dark:text-[#f7f1df]">{{ $item->productOption->product->name }}</p>
                            <p class="truncate text-xs text-[#315e52] dark:text-[#d2e7df]/65">{{ $item->productOption->name }}</p>
                        </div>
                        <button type="button" wire:click="removeFromCart({{ $item->id }})" aria-label="إزالة {{ $item->productOption->product->name }}" class="shrink-0 text-xs font-bold text-red-700 hover:underline dark:text-red-300">إزالة</button>
                    </div>
                    <div class="mt-3 flex items-center justify-between gap-3">
                        <div class="inline-flex items-center rounded-xl border border-[#2a8069]/16 dark:border-white/12">
                            <button type="button" wire:click="updateCartItem({{ $item->id }}, {{ max(1, $item->quantity - 1) }})" class="size-9 text-lg" aria-label="إنقاص الكمية">−</button>
                            <span class="min-w-8 text-center text-sm font-bold">{{ $item->quantity }}</span>
                            <button type="button" wire:click="updateCartItem({{ $item->id }}, {{ min(99, $item->quantity + 1) }})" class="size-9 text-lg" aria-label="زيادة الكمية">+</button>
                        </div>
                        <p class="text-sm font-bold text-[#007a52] dark:text-[#6ee7b7]"><x-money :amount-baisa="$item->productOption->price_baisa * $item->quantity" :currency="$item->productOption->currency" /></p>
                    </div>
                    <label class="mt-3 block text-xs font-semibold text-[#315e52] dark:text-[#d2e7df]/70">ملاحظة
                        <input type="text" value="{{ $item->note }}" wire:blur="updateCartItemNote({{ $item->id }}, $event.target.value)" maxlength="5000" class="mt-1 min-h-10 w-full rounded-xl border border-[#2a8069]/16 bg-transparent px-3 text-sm focus:border-[#007a52] focus:outline-none focus:ring-2 focus:ring-[#007a52]/15 dark:border-white/12">
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

        <button type="button" wire:click="openCheckout" wire:loading.attr="disabled" @disabled(! $orderingEnabled) class="mt-5 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-[#007a52] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#006746] disabled:cursor-not-allowed disabled:opacity-50">
            <x-hugeicon name="payment-02" class="text-lg" /> إتمام الطلب والدفع
        </button>
    @endif
</aside>
