<section id="menu" class="scroll-mt-24 bg-[#f6fbf8] dark:bg-[#07120f]" aria-labelledby="store-menu-title">
    <div class="mx-auto w-full max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-sm font-bold tracking-[0.18em] text-[#b07c00]">اطلب مسبقًا</p>
                <h2 id="store-menu-title" class="mt-3 font-heading text-3xl font-bold text-[#123329] lg:text-5xl dark:text-[#f7f1df]">القائمة الافتتاحية</h2>
                <p class="mt-4 max-w-[62ch] leading-8 text-[#315e52] dark:text-[#d2e7df]/76">اختر ما يناسبك، أضفه إلى السلة، ثم حدّد وقت الاستلام. الأسعار بالريال العُماني وتشمل ضريبة القيمة المضافة في الملخص النهائي.</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-[#315e52] dark:text-[#d2e7df]/70">{{ $cart?->items->sum('quantity') ?? 0 }} منتجات في السلة</span>
                <button type="button" wire:click="openCheckout" wire:loading.attr="disabled" @disabled(! $orderingEnabled || ! $cart?->items->isNotEmpty()) class="inline-flex min-h-11 items-center gap-2 rounded-sm border border-[#007a52]/25 bg-white px-4 py-2 text-sm font-bold text-[#007a52] transition hover:border-[#007a52] disabled:cursor-not-allowed disabled:opacity-45 dark:border-[#6ee7b7]/30 dark:bg-white/5 dark:text-[#6ee7b7]">
                    <x-hugeicon name="wallet-02" class="text-lg" /> السلة والدفع
                </button>
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
            <div class="mt-10 grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(320px,0.42fr)]">
                <div class="grid gap-8 sm:grid-cols-2">
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

                <aside class="h-fit rounded-sm border border-[#2a8069]/16 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5" aria-labelledby="cart-title">
                    <div class="flex items-center justify-between gap-3"><h2 id="cart-title" class="font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">سلتك</h2><span class="rounded-full bg-[#e9f7f0] px-2.5 py-1 text-xs font-bold text-[#006746] dark:bg-[#0c2a20] dark:text-[#a7f3d0]">{{ $cart?->items->sum('quantity') ?? 0 }}</span></div>
                    @if (! $cart || $cart->items->isEmpty())
                        <div class="py-12 text-center"><x-hugeicon name="wallet-02" class="mx-auto text-4xl text-[#007a52]/60" /><p class="mt-4 text-sm leading-7 text-[#315e52] dark:text-[#d2e7df]/70">السلة فارغة. أضف مشروبك المفضل لتبدأ.</p></div>
                    @else
                        <div class="mt-5 grid gap-4">
                            @foreach ($cart->items as $item)
                                <div wire:key="cart-item-{{ $item->id }}" class="border-b border-[#2a8069]/12 pb-4 last:border-0 dark:border-white/10">
                                    <div class="flex items-start justify-between gap-3"><div><p class="font-bold text-[#123329] dark:text-[#f7f1df]">{{ $item->productOption->product->name }}</p><p class="text-xs text-[#315e52] dark:text-[#d2e7df]/65">{{ $item->productOption->name }}</p></div><button type="button" wire:click="removeFromCart({{ $item->id }})" aria-label="إزالة {{ $item->productOption->product->name }}" class="text-xs font-bold text-red-700 hover:underline dark:text-red-300">إزالة</button></div>
                                    <div class="mt-3 flex items-center justify-between gap-3"><div class="inline-flex items-center rounded-sm border border-[#2a8069]/16 dark:border-white/12"><button type="button" wire:click="updateCartItem({{ $item->id }}, {{ max(1, $item->quantity - 1) }})" class="size-9 text-lg" aria-label="إنقاص الكمية">−</button><span class="min-w-8 text-center text-sm font-bold">{{ $item->quantity }}</span><button type="button" wire:click="updateCartItem({{ $item->id }}, {{ min(99, $item->quantity + 1) }})" class="size-9 text-lg" aria-label="زيادة الكمية">+</button></div><p class="text-sm font-bold text-[#007a52] dark:text-[#6ee7b7]"><x-money :amount-baisa="$item->productOption->price_baisa * $item->quantity" :currency="$item->productOption->currency" /></p></div>
                                    <label class="mt-3 block text-xs font-semibold text-[#315e52] dark:text-[#d2e7df]/70">ملاحظة <input type="text" value="{{ $item->note }}" wire:blur="updateCartItemNote({{ $item->id }}, $event.target.value)" maxlength="5000" class="mt-1 min-h-10 w-full rounded-sm border border-[#2a8069]/16 bg-transparent px-3 text-sm focus:border-[#007a52] focus:outline-none focus:ring-2 focus:ring-[#007a52]/15 dark:border-white/12"></label>
                                </div>
                            @endforeach
                        </div>
                        @if ($quote)<dl class="mt-5 grid gap-2 border-t border-[#2a8069]/12 pt-4 text-sm dark:border-white/10"><div class="flex justify-between"><dt>المجموع</dt><dd class="font-bold"><x-money :amount-baisa="$quote['subtotal_baisa']" currency="OMR" /></dd></div><div class="flex justify-between text-[#315e52] dark:text-[#d2e7df]/65"><dt>ضريبة القيمة المضافة</dt><dd><x-money :amount-baisa="$quote['vat_baisa']" currency="OMR" /></dd></div><div class="flex justify-between border-t border-[#2a8069]/12 pt-3 text-base font-bold dark:border-white/10"><dt>الإجمالي</dt><dd class="text-[#007a52] dark:text-[#6ee7b7]"><x-money :amount-baisa="$quote['total_baisa']" currency="OMR" /></dd></div></dl>@endif
                        <button type="button" wire:click="openCheckout" wire:loading.attr="disabled" @disabled(! $orderingEnabled) class="mt-5 inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-sm bg-[#007a52] px-4 py-3 text-sm font-bold text-white transition hover:bg-[#006746] disabled:cursor-not-allowed disabled:opacity-50"><x-hugeicon name="payment-02" class="text-lg" /> إتمام الطلب والدفع</button>
                    @endif
                </aside>
            </div>
        @endif
    </div>

    @if ($cart?->items->isNotEmpty())
        <button type="button" wire:click="openCart" wire:loading.attr="disabled" aria-controls="store-cart-drawer" aria-label="فتح السلة" class="fixed bottom-24 start-4 z-50 inline-flex min-h-14 items-center gap-3 rounded-full bg-[#007a52] px-4 py-3 text-sm font-bold text-white shadow-xl shadow-[#007a52]/25 transition hover:-translate-y-0.5 hover:bg-[#006746] focus:outline-none focus:ring-4 focus:ring-[#007a52]/25 lg:bottom-8 lg:start-8">
            <span class="relative grid size-9 place-items-center rounded-full bg-white/15"><x-hugeicon name="wallet-02" class="text-xl" /><span class="absolute -right-1 -top-1 grid min-w-5 place-items-center rounded-full bg-[#f0c96a] px-1 text-[10px] font-black leading-5 text-[#123329]">{{ $cart->items->sum('quantity') }}</span></span>
            <span>عرض السلة</span>
        </button>
    @endif

    @if ($cartOpen)
        <div wire:keydown.escape.window="closeCart" class="fixed inset-0 z-[70]" role="dialog" aria-modal="true" aria-labelledby="store-cart-drawer-title">
            <button type="button" wire:click="closeCart" class="absolute inset-0 bg-[#07120f]/50 backdrop-blur-[2px]" aria-label="إغلاق السلة"></button>
            <div id="store-cart-drawer-shell" class="absolute inset-x-4 bottom-4 max-h-[calc(100dvh-7rem)] overflow-y-auto rounded-2xl shadow-2xl sm:inset-x-auto sm:start-6 sm:top-24 sm:bottom-auto sm:w-[min(26rem,calc(100vw-3rem))]">
                @include('livewire.store.partials.cart', ['cart' => $cart, 'quote' => $quote, 'orderingEnabled' => $orderingEnabled, 'cartId' => 'store-cart-drawer', 'keyPrefix' => 'drawer'])
            </div>
        </div>
    @endif

    @if ($checkoutOpen)
        <div class="fixed inset-0 z-[80] overflow-y-auto bg-[#07120f]/70 px-4 py-8 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="checkout-title">
            <div class="mx-auto max-w-2xl rounded-sm border border-[#2a8069]/18 bg-[#f6fbf8] p-6 shadow-2xl dark:border-white/10 dark:bg-[#0c1e19] sm:p-8">
                <div class="flex items-start justify-between gap-4"><div><p class="text-sm font-bold tracking-[0.18em] text-[#b07c00]">الخطوة الأخيرة</p><h2 id="checkout-title" class="mt-2 font-heading text-3xl font-bold text-[#123329] dark:text-[#f7f1df]">بيانات الاستلام</h2></div><button type="button" wire:click="$set('checkoutOpen', false)" class="size-10 rounded-sm border border-[#2a8069]/16 text-xl dark:border-white/12" aria-label="إغلاق">×</button></div>
                @if ($quote)
                    <section class="rounded-sm border border-[#007a52]/18 bg-white p-4 dark:border-white/10 dark:bg-white/5" aria-labelledby="checkout-summary-title">
                        <h3 id="checkout-summary-title" class="font-heading text-lg font-bold text-[#123329] dark:text-[#f7f1df]">ملخص الطلب</h3>
                        <div class="mt-3 grid gap-3 text-sm">
                            @foreach ($quote['items'] as $summaryItem)
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="font-bold text-[#123329] dark:text-[#f7f1df]">{{ $summaryItem['product_name'] }}</p>
                                        <p class="text-xs text-[#315e52] dark:text-[#d2e7df]/65">{{ $summaryItem['option_name'] }} × {{ $summaryItem['quantity'] }}</p>
                                    </div>
                                    <x-money :amount-baisa="$summaryItem['line_total_baisa']" :currency="$summaryItem['currency']" />
                                </div>
                            @endforeach
                        </div>
                        <dl class="mt-4 grid gap-2 border-t border-[#2a8069]/12 pt-3 text-sm dark:border-white/10">
                            <div class="flex justify-between"><dt>المجموع</dt><dd><x-money :amount-baisa="$quote['subtotal_baisa']" currency="OMR" /></dd></div>
                            <div class="flex justify-between"><dt>ضريبة القيمة المضافة</dt><dd><x-money :amount-baisa="$quote['vat_baisa']" currency="OMR" /></dd></div>
                            <div class="flex justify-between border-t border-[#2a8069]/12 pt-2 font-bold dark:border-white/10"><dt>الإجمالي</dt><dd class="text-[#007a52] dark:text-[#6ee7b7]"><x-money :amount-baisa="$quote['total_baisa']" currency="OMR" /></dd></div>
                        </dl>
                    </section>
                @endif
                <form wire:submit="placeOrder" class="mt-7 grid gap-5">
                    <div class="grid gap-5 sm:grid-cols-2"><label class="grid gap-2 text-sm font-bold">الاسم <input wire:model="customerName" required class="min-h-12 rounded-sm border border-[#2a8069]/18 bg-white px-3 font-normal outline-none focus:border-[#007a52] focus:ring-2 focus:ring-[#007a52]/15 dark:border-white/12 dark:bg-white/5"></label><label class="grid gap-2 text-sm font-bold">رقم الهاتف <input wire:model="customerPhone" required inputmode="tel" class="min-h-12 rounded-sm border border-[#2a8069]/18 bg-white px-3 font-normal outline-none focus:border-[#007a52] focus:ring-2 focus:ring-[#007a52]/15 dark:border-white/12 dark:bg-white/5"></label></div>
                    <label class="grid gap-2 text-sm font-bold">البريد الإلكتروني <input wire:model="customerEmail" type="email" class="min-h-12 rounded-sm border border-[#2a8069]/18 bg-white px-3 font-normal outline-none focus:border-[#007a52] focus:ring-2 focus:ring-[#007a52]/15 dark:border-white/12 dark:bg-white/5"></label>
                    <div class="grid gap-5 sm:grid-cols-2"><label class="grid gap-2 text-sm font-bold">اسم المستلم (اختياري) <input wire:model="recipientName" class="min-h-12 rounded-sm border border-[#2a8069]/18 bg-white px-3 font-normal outline-none focus:border-[#007a52] focus:ring-2 focus:ring-[#007a52]/15 dark:border-white/12 dark:bg-white/5"></label><label class="grid gap-2 text-sm font-bold">هاتف المستلم (اختياري) <input wire:model="recipientPhone" inputmode="tel" class="min-h-12 rounded-sm border border-[#2a8069]/18 bg-white px-3 font-normal outline-none focus:border-[#007a52] focus:ring-2 focus:ring-[#007a52]/15 dark:border-white/12 dark:bg-white/5"></label></div>
                    <fieldset class="grid gap-3"><legend class="text-sm font-bold">وقت الاستلام</legend><div class="grid gap-3 sm:grid-cols-2"><label class="flex cursor-pointer items-center gap-3 rounded-sm border border-[#2a8069]/18 bg-white p-3 dark:border-white/12 dark:bg-white/5"><input type="radio" wire:model.live="pickupType" value="immediate"> <span><span class="block font-bold">في أقرب وقت</span><span class="text-xs text-[#315e52] dark:text-[#d2e7df]/65">نجهزه عند وصولك</span></span></label><label class="flex cursor-pointer items-center gap-3 rounded-sm border border-[#2a8069]/18 bg-white p-3 dark:border-white/12 dark:bg-white/5"><input type="radio" wire:model.live="pickupType" value="scheduled"> <span><span class="block font-bold">موعد محدد</span><span class="text-xs text-[#315e52] dark:text-[#d2e7df]/65">قبل الموعد بأربع ساعات على الأقل</span></span></label></div></fieldset>
                    @if ($pickupType === 'scheduled')<label class="grid gap-2 text-sm font-bold">موعد الاستلام <input wire:model="pickupAt" type="datetime-local" required class="min-h-12 rounded-sm border border-[#2a8069]/18 bg-white px-3 font-normal outline-none focus:border-[#007a52] focus:ring-2 focus:ring-[#007a52]/15 dark:border-white/12 dark:bg-white/5"></label>@endif
                    <label class="grid gap-2 text-sm font-bold">ملاحظة للطلب <textarea wire:model="orderNote" rows="3" maxlength="5000" class="rounded-sm border border-[#2a8069]/18 bg-white px-3 py-3 font-normal outline-none focus:border-[#007a52] focus:ring-2 focus:ring-[#007a52]/15 dark:border-white/12 dark:bg-white/5"></textarea><span class="text-xs font-normal text-[#315e52] dark:text-[#d2e7df]/65">حتى ٥٠ كلمة</span></label>
                    <div class="rounded-sm border border-[#007a52]/18 bg-[#e9f7f0] p-4 text-sm leading-7 text-[#006746] dark:border-[#6ee7b7]/20 dark:bg-[#0c2a20] dark:text-[#a7f3d0]">بعد تأكيد الطلب ستنتقل إلى بوابة ثواني للدفع الآمن. لا نحتفظ ببيانات بطاقتك.</div>
                    <button type="submit" wire:loading.attr="disabled" wire:target="placeOrder" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-sm bg-[#007a52] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#006746] disabled:cursor-wait disabled:opacity-55"><span wire:loading.remove wire:target="placeOrder">متابعة إلى الدفع</span><span wire:loading wire:target="placeOrder">جارٍ تجهيز الطلب…</span><x-hugeicon name="arrow-left-02" class="text-lg" /></button>
                </form>
            </div>
        </div>
    @endif
</section>
