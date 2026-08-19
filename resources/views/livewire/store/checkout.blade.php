<section id="store-checkout" class="bg-[#f6fbf8] dark:bg-[#07120f]" aria-labelledby="store-checkout-title">
    <div class="mx-auto w-full max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-20">
        <div class="mx-auto max-w-3xl text-center">
            <p class="text-sm font-bold uppercase tracking-[0.18em] text-[#b07c00]">الخطوة الأخيرة</p>
            <h1 id="store-checkout-title" class="mt-3 font-heading text-4xl font-bold text-[#123329] lg:text-5xl dark:text-[#f7f1df]">إتمام الطلب</h1>
            <p class="mt-4 leading-8 text-[#315e52] dark:text-[#d2e7df]/76">أدخل بيانات الاستلام، راجع طلبك، ثم انتقل إلى بوابة الدفع الآمنة.</p>
        </div>

        <button type="button" wire:click="backToStore" class="mt-8 inline-flex min-h-11 items-center gap-2 rounded-xl border border-[#2a8069]/18 bg-white/70 px-4 py-2 text-sm font-bold text-[#007a52] transition hover:border-[#007a52] dark:border-white/12 dark:bg-white/5 dark:text-[#6ee7b7]">
            <x-hugeicon name="arrow-right-02" class="text-lg" /> العودة إلى قائمة القهوة
        </button>

        @if (! $cart || $cart->items->isEmpty())
            <div class="mx-auto mt-10 max-w-2xl rounded-2xl border border-dashed border-[#2a8069]/25 bg-white p-10 text-center shadow-sm dark:border-white/15 dark:bg-white/5">
                <x-hugeicon name="wallet-02" class="mx-auto text-5xl text-[#007a52]/60" />
                <h2 class="mt-5 font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">السلة فارغة</h2>
                <p class="mt-3 leading-7 text-[#315e52] dark:text-[#d2e7df]/70">أضف منتجات إلى السلة قبل الانتقال إلى إتمام الطلب.</p>
                <a href="{{ route('coffee') }}#menu" class="mt-6 inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-[#007a52] px-6 py-3 text-sm font-bold text-white transition hover:bg-[#006746]">تصفح القائمة <x-hugeicon name="arrow-left-02" class="text-lg" /></a>
            </div>
        @else
            @if ($cartError)
                <div role="alert" class="mt-8 rounded-xl border border-[#b45309]/25 bg-[#fff8e7] px-4 py-3 text-sm font-semibold text-[#92400e] dark:border-[#f0c96a]/20 dark:bg-[#2d2410] dark:text-[#f9d98b]">{{ $cartError }}</div>
            @endif
            @if ($errors->any())
                <div role="alert" class="mt-8 rounded-xl border border-red-600/20 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 dark:border-red-300/20 dark:bg-red-950/30 dark:text-red-200">{{ $errors->first() }}</div>
            @endif

            <div class="mt-10 grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(20rem,0.42fr)] lg:items-start">
                <form wire:submit="placeOrder" class="order-2 grid gap-6 rounded-2xl border border-[#2a8069]/16 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5 sm:p-8 lg:order-1">
                    <section aria-labelledby="checkout-customer-title">
                        <h2 id="checkout-customer-title" class="font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">بيانات العميل</h2>
                        <div class="mt-5 grid gap-5 sm:grid-cols-2">
                            <label class="grid gap-2 text-sm font-bold">الاسم
                                <input wire:model="customerName" required autocomplete="name" class="min-h-12 rounded-xl border border-[#2a8069]/18 bg-[#f6fbf8] px-3 font-normal outline-none focus:border-[#007a52] focus:ring-2 focus:ring-[#007a52]/15 dark:border-white/12 dark:bg-white/5">
                            </label>
                            <label class="grid gap-2 text-sm font-bold">رقم الهاتف
                                <input wire:model="customerPhone" required inputmode="tel" autocomplete="tel" class="min-h-12 rounded-xl border border-[#2a8069]/18 bg-[#f6fbf8] px-3 font-normal outline-none focus:border-[#007a52] focus:ring-2 focus:ring-[#007a52]/15 dark:border-white/12 dark:bg-white/5">
                            </label>
                        </div>
                        <label class="mt-5 grid gap-2 text-sm font-bold">البريد الإلكتروني <span class="font-normal text-[#315e52]/70">(اختياري)</span>
                            <input wire:model="customerEmail" type="email" autocomplete="email" class="min-h-12 rounded-xl border border-[#2a8069]/18 bg-[#f6fbf8] px-3 font-normal outline-none focus:border-[#007a52] focus:ring-2 focus:ring-[#007a52]/15 dark:border-white/12 dark:bg-white/5">
                        </label>
                    </section>

                    <section class="border-t border-[#2a8069]/12 pt-6 dark:border-white/10" aria-labelledby="checkout-recipient-title">
                        <h2 id="checkout-recipient-title" class="font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">بيانات المستلم <span class="text-base font-normal text-[#315e52]/65">(اختيارية)</span></h2>
                        <div class="mt-5 grid gap-5 sm:grid-cols-2">
                            <label class="grid gap-2 text-sm font-bold">اسم المستلم
                                <input wire:model="recipientName" autocomplete="shipping name" class="min-h-12 rounded-xl border border-[#2a8069]/18 bg-[#f6fbf8] px-3 font-normal outline-none focus:border-[#007a52] focus:ring-2 focus:ring-[#007a52]/15 dark:border-white/12 dark:bg-white/5">
                            </label>
                            <label class="grid gap-2 text-sm font-bold">هاتف المستلم
                                <input wire:model="recipientPhone" inputmode="tel" autocomplete="shipping tel" class="min-h-12 rounded-xl border border-[#2a8069]/18 bg-[#f6fbf8] px-3 font-normal outline-none focus:border-[#007a52] focus:ring-2 focus:ring-[#007a52]/15 dark:border-white/12 dark:bg-white/5">
                            </label>
                        </div>
                    </section>

                    <section class="border-t border-[#2a8069]/12 pt-6 dark:border-white/10" aria-labelledby="checkout-pickup-title">
                        <h2 id="checkout-pickup-title" class="font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">وقت الاستلام</h2>
                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-[#2a8069]/18 bg-[#f6fbf8] p-4 dark:border-white/12 dark:bg-white/5">
                                <input type="radio" wire:model.live="pickupType" value="immediate" class="mt-1">
                                <span><span class="block font-bold">في أقرب وقت</span><span class="mt-1 block text-xs leading-5 text-[#315e52] dark:text-[#d2e7df]/65">نجهزه عند وصولك.</span></span>
                            </label>
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-[#2a8069]/18 bg-[#f6fbf8] p-4 dark:border-white/12 dark:bg-white/5">
                                <input type="radio" wire:model.live="pickupType" value="scheduled" class="mt-1">
                                <span><span class="block font-bold">موعد محدد</span><span class="mt-1 block text-xs leading-5 text-[#315e52] dark:text-[#d2e7df]/65">قبل الموعد بأربع ساعات على الأقل.</span></span>
                            </label>
                        </div>
                        @if ($pickupType === 'scheduled')
                            <label class="mt-5 grid gap-2 text-sm font-bold">موعد الاستلام
                                <input wire:model="pickupAt" type="datetime-local" required class="min-h-12 rounded-xl border border-[#2a8069]/18 bg-[#f6fbf8] px-3 font-normal outline-none focus:border-[#007a52] focus:ring-2 focus:ring-[#007a52]/15 dark:border-white/12 dark:bg-white/5">
                            </label>
                        @endif
                    </section>

                    <section class="border-t border-[#2a8069]/12 pt-6 dark:border-white/10" aria-labelledby="checkout-note-title">
                        <h2 id="checkout-note-title" class="font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">ملاحظة للطلب <span class="text-base font-normal text-[#315e52]/65">(اختيارية)</span></h2>
                        <textarea wire:model="orderNote" rows="4" maxlength="5000" class="mt-5 w-full rounded-xl border border-[#2a8069]/18 bg-[#f6fbf8] px-3 py-3 font-normal outline-none focus:border-[#007a52] focus:ring-2 focus:ring-[#007a52]/15 dark:border-white/12 dark:bg-white/5"></textarea>
                        <p class="mt-2 text-xs text-[#315e52] dark:text-[#d2e7df]/65">حتى ٥٠ كلمة.</p>
                    </section>

                    <div class="rounded-xl border border-[#007a52]/18 bg-[#e9f7f0] p-4 text-sm leading-7 text-[#006746] dark:border-[#6ee7b7]/20 dark:bg-[#0c2a20] dark:text-[#a7f3d0]">بعد تأكيد الطلب ستنتقل إلى بوابة ثواني للدفع الآمن. لا نحتفظ ببيانات بطاقتك.</div>

                    <button type="submit" wire:loading.attr="disabled" wire:target="placeOrder" @disabled(! $orderingEnabled || ! $quote) class="inline-flex min-h-14 items-center justify-center gap-2 rounded-xl bg-[#007a52] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#006746] disabled:cursor-wait disabled:opacity-55">
                        <span wire:loading.remove wire:target="placeOrder">تأكيد الطلب والمتابعة إلى الدفع</span>
                        <span wire:loading wire:target="placeOrder">جارٍ تجهيز الطلب…</span>
                        <x-hugeicon name="arrow-left-02" class="text-lg" />
                    </button>
                </form>

                <aside class="order-1 h-fit rounded-2xl border border-[#2a8069]/16 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5 lg:sticky lg:top-28 lg:order-2" aria-labelledby="checkout-summary-title">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#b07c00]">مراجعة</p>
                            <h2 id="checkout-summary-title" class="mt-1 font-heading text-2xl font-bold text-[#123329] dark:text-[#f7f1df]">ملخص الطلب</h2>
                        </div>
                        <span class="rounded-full bg-[#e9f7f0] px-2.5 py-1 text-xs font-bold text-[#006746] dark:bg-[#0c2a20] dark:text-[#a7f3d0]">{{ $cart->items->sum('quantity') }}</span>
                    </div>
                    <div class="mt-5 grid gap-4">
                        @foreach ($cart->items as $item)
                            <div class="flex items-start justify-between gap-4 border-b border-[#2a8069]/12 pb-4 last:border-0 dark:border-white/10">
                                <div class="min-w-0"><p class="truncate font-bold text-[#123329] dark:text-[#f7f1df]">{{ $item->productOption->product->name }}</p><p class="mt-1 truncate text-xs text-[#315e52] dark:text-[#d2e7df]/65">{{ $item->productOption->name }} × {{ $item->quantity }}</p></div>
                                <x-money :amount-baisa="$item->productOption->price_baisa * $item->quantity" :currency="$item->productOption->currency" />
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
                </aside>
            </div>
        @endif
    </div>
</section>
