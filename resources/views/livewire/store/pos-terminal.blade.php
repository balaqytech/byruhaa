<div x-data x-on:pos-order-completed.window="$nextTick(() => $refs.completedOrder?.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center' }))" class="space-y-5 xl:space-y-7">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold tracking-[0.12em] text-[#718778] dark:text-[#a7b9a9]">نقطة البيع / طلب جديد</p>
            <h1 class="mt-2 font-heading text-3xl font-black sm:text-4xl">ابدأ طلبًا جديدًا</h1>
            <p class="mt-2 text-sm text-[#687d70] dark:text-[#afc0b3]">استقبل طلب ضيف أو قائد، ثم أكّد الدفع نقدًا أو من محفظة القائد.</p>
        </div>
        <span class="rounded-full border border-[#cbddc5] bg-[#e8f1df] px-4 py-2 text-xs font-bold text-[#31553f] dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200">{{ $paymentMethod === 'cash' ? 'الدفع النقدي' : 'محفظة القائد' }}</span>
    </div>

    @if ($completedReference)
        <div x-ref="completedOrder" role="status" tabindex="-1" class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[#aed6b6] bg-[#e1f4e3] px-5 py-4 text-[#1a5a34] dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-100 sm:px-6 sm:py-5">
            <div><p class="font-heading text-xl font-black">{{ $completedPaymentMethod === 'cash' ? 'تم استلام النقد وتأكيد الطلب' : 'تم الخصم وتأكيد الطلب' }}</p><p class="mt-1 text-sm">رقم الطلب جاهز للمتابعة في شاشة الباريستا.</p></div>
            <div class="flex flex-wrap items-center gap-3 text-left"><div><strong class="block rounded-lg bg-white/75 px-4 py-2 font-mono text-lg dark:bg-black/20" dir="ltr">{{ $completedReference }}</strong>@if ($completedPaymentMethod === 'cash') <p class="mt-2 text-sm font-bold">الباقي: {{ number_format(($completedCashChangeBaisa ?? 0) / 1000, 3) }} ر.ع</p> @endif</div><a href="{{ route('cashier.orders.receipt', $completedOrderId) }}" target="_blank" rel="noopener" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-[#204d3a] px-5 text-sm font-bold text-white hover:bg-[#2c674c] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#204d3a]">طباعة الإيصال</a></div>
        </div>
    @endif

    <div class="grid items-start gap-5 md:grid-cols-[minmax(0,1fr)_minmax(320px,0.82fr)] xl:gap-7">
        <section class="space-y-5">
            <div class="rounded-2xl border border-[#dee5d9] bg-white p-5 shadow-sm dark:border-white/10 dark:bg-[#142621] sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div><h2 class="font-heading text-2xl font-black">المنتجات</h2><p class="mt-1 text-xs text-[#718677] dark:text-[#adc2b3]">اضغط على المنتج لإضافته إلى الطلب</p></div>
                    <span class="text-xs font-semibold text-[#718677] dark:text-[#adc2b3]">حتى ٥٠ منتجًا</span>
                </div>
                <label for="product-search" class="mt-6 block text-sm font-bold">ابحث عن منتج أو رمز الصنف</label>
                <input id="product-search" wire:model.live.debounce.250ms="search" type="search" class="mt-2 w-full rounded-xl border border-[#d5dfd3] bg-[#f7f9f5] px-4 py-3 text-base outline-none transition focus:border-[#5a9a70] focus:ring-2 focus:ring-[#5a9a70]/20 dark:border-white/15 dark:bg-[#0d1c19]" placeholder="اسم المنتج أو SKU" />
                <div class="mt-5 grid gap-3 sm:grid-cols-2 2xl:grid-cols-3">
                    @forelse ($catalog as $option)
                        <button type="button" wire:key="pos-option-{{ $option->id }}" wire:click="addOption({{ $option->id }})" wire:loading.attr="disabled" aria-label="إضافة {{ $option->product->name }}، {{ $option->name }} إلى الطلب" class="group flex min-h-28 flex-col justify-between rounded-xl border border-[#dde5da] bg-[#fbfcf8] p-3 text-right transition hover:border-[#73a886] hover:bg-[#f1f8ea] hover:shadow-sm focus-visible:outline-2 focus-visible:outline-[#3b8261] disabled:opacity-60 dark:border-white/10 dark:bg-[#10211c] dark:hover:bg-[#1c3b2d] sm:p-4">
                            <span class="font-semibold leading-6">{{ $option->product->name }}</span>
                            <span class="mt-3 flex items-end justify-between gap-2 text-xs text-[#697e70] dark:text-[#adc2b3]"><span>{{ $option->name }}<br><span class="text-[11px]">السعر الأساسي</span></span><strong class="whitespace-nowrap text-sm text-[#204d37] dark:text-[#bfe8bc]" dir="ltr">{{ number_format($option->price_baisa / 1000, 3) }} ر.ع</strong></span>
                        </button>
                    @empty
                        <p class="col-span-full rounded-xl bg-[#f7f9f5] px-5 py-8 text-center text-sm text-[#708277] dark:bg-white/5 dark:text-[#adc2b3]">لا توجد منتجات مطابقة.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="space-y-4 md:sticky md:top-4 xl:top-6">
            <div class="rounded-2xl border border-[#dee5d9] bg-white p-5 shadow-sm dark:border-white/10 dark:bg-[#142621] sm:p-6">
                <div class="flex items-center justify-between"><h2 class="font-heading text-2xl font-black">الطلب الحالي</h2><span class="rounded-full bg-[#eaf1e5] px-3 py-1 text-xs font-bold dark:bg-white/10">{{ array_sum($cart) }} عناصر</span></div>
                <div class="mt-4 divide-y divide-[#e8ece6] dark:divide-white/10">
                    @forelse ($cartOptions as $option)
                        <div wire:key="pos-cart-{{ $option->id }}" class="space-y-2 py-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0"><p class="text-sm font-bold leading-6">{{ $option->product->name }}</p><p class="text-xs text-[#718677] dark:text-[#adc2b3]">{{ $option->name }}</p></div>
                                <button type="button" wire:click="removeOption({{ $option->id }})" aria-label="إزالة {{ $option->product->name }} من الطلب" class="min-h-11 shrink-0 rounded-lg px-3 text-sm font-bold text-rose-700 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-400/10">إزالة</button>
                            </div>
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" wire:click="decrementOption({{ $option->id }})" aria-label="إنقاص كمية {{ $option->product->name }}" class="grid size-11 place-items-center rounded-lg border border-[#d7e0d4] text-xl hover:bg-[#edf5e8] dark:border-white/15 dark:hover:bg-white/10">−</button>
                                <output aria-label="الكمية" class="min-w-8 text-center text-base font-black">{{ $cart[$option->id] }}</output>
                                <button type="button" wire:click="addOption({{ $option->id }})" aria-label="زيادة كمية {{ $option->product->name }}" class="grid size-11 place-items-center rounded-lg border border-[#d7e0d4] text-xl hover:bg-[#edf5e8] dark:border-white/15 dark:hover:bg-white/10">+</button>
                            </div>
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-[#718677] dark:text-[#adc2b3]">اختر منتجًا للبدء.</p>
                    @endforelse
                </div>
                @error('cart') <p role="alert" class="mt-3 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p> @enderror
            </div>

            <div class="rounded-2xl border border-[#dee5d9] bg-white p-5 shadow-sm dark:border-white/10 dark:bg-[#142621] sm:p-6">
                <div class="mb-4 flex items-center gap-3"><span class="grid size-8 place-items-center rounded-full bg-[#dcecc7] text-sm font-black text-[#28533c]">١</span><h2 class="font-heading text-xl font-black">العميل وطريقة الدفع</h2></div>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" wire:click="selectBuyerType('guest')" aria-pressed="{{ $buyerType === 'guest' ? 'true' : 'false' }}" class="min-h-14 rounded-xl border px-4 py-3 text-base font-bold transition {{ $buyerType === 'guest' ? 'border-[#5f9d71] bg-[#e9f3e2] text-[#28563b]' : 'border-[#d7e0d4] hover:bg-[#f5f8f2] dark:border-white/15 dark:hover:bg-white/5' }}">ضيف</button>
                    <button type="button" wire:click="selectBuyerType('minor')" aria-pressed="{{ $buyerType === 'minor' ? 'true' : 'false' }}" class="min-h-14 rounded-xl border px-4 py-3 text-base font-bold transition {{ $buyerType === 'minor' ? 'border-[#5f9d71] bg-[#e9f3e2] text-[#28563b]' : 'border-[#d7e0d4] hover:bg-[#f5f8f2] dark:border-white/15 dark:hover:bg-white/5' }}">قائد</button>
                </div>
                @if ($buyerType === 'minor')
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <button type="button" wire:click="selectPaymentMethod('wallet')" aria-pressed="{{ $paymentMethod === 'wallet' ? 'true' : 'false' }}" class="min-h-12 rounded-xl border px-3 py-2.5 text-sm font-bold transition {{ $paymentMethod === 'wallet' ? 'border-[#5f9d71] bg-[#e9f3e2] text-[#28563b]' : 'border-[#d7e0d4] dark:border-white/15' }}">المحفظة</button>
                        <button type="button" wire:click="selectPaymentMethod('cash')" aria-pressed="{{ $paymentMethod === 'cash' ? 'true' : 'false' }}" class="min-h-12 rounded-xl border px-3 py-2.5 text-sm font-bold transition {{ $paymentMethod === 'cash' ? 'border-[#5f9d71] bg-[#e9f3e2] text-[#28563b]' : 'border-[#d7e0d4] dark:border-white/15' }}">نقدًا</button>
                    </div>
                    <div x-data="cashierQrScanner" class="mt-4">
                        <p class="text-xs leading-6 text-[#718677] dark:text-[#adc2b3]">قرّب بطاقة القائد من كاميرا الجهاز، أو استخدم قارئ QR الخارجي.</p>
                        <button type="button" @click="open()" :disabled="isOpen || isStarting" class="mt-3 flex min-h-14 w-full items-center justify-center rounded-xl bg-[#204d3a] px-4 text-base font-black text-white transition hover:bg-[#2c674c] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#204d3a] disabled:opacity-60">فتح كاميرا الجهاز لمسح QR</button>
                        <div x-show="isOpen" x-cloak class="mt-3 overflow-hidden rounded-xl bg-[#0d1c19] p-3 text-white">
                            <video x-ref="video" autoplay muted playsinline class="aspect-square max-h-72 w-full rounded-lg bg-black object-cover"></video>
                            <div class="mt-3 flex items-center justify-between gap-3 text-sm"><span x-text="isStarting ? 'جارٍ تشغيل الكاميرا…' : 'ضع رمز QR داخل الإطار'"></span><button type="button" @click="close()" class="min-h-11 rounded-lg border border-white/30 px-4 font-bold">إغلاق الكاميرا</button></div>
                        </div>
                        <p x-show="error" x-cloak x-text="error" role="alert" class="mt-2 text-sm text-rose-700 dark:text-rose-300"></p>
                        <form wire:submit="scan" class="mt-3 flex gap-2">
                            <input wire:model="scanToken" type="password" dir="ltr" autocomplete="off" autocapitalize="none" spellcheck="false" aria-label="رمز بطاقة QR من قارئ خارجي أو إدخال يدوي" placeholder="رمز البطاقة أو قارئ QR" class="min-h-12 min-w-0 flex-1 rounded-xl border border-[#d5dfd3] bg-[#f7f9f5] px-3 py-3 font-mono text-sm outline-none focus:border-[#5a9a70] dark:border-white/15 dark:bg-[#0d1c19]" />
                            <button type="submit" wire:loading.attr="disabled" class="min-h-12 rounded-xl border border-[#204d3a] px-4 py-3 text-sm font-bold text-[#204d3a] hover:bg-[#eaf2e5] disabled:opacity-60 dark:border-emerald-300 dark:text-emerald-200">تحقق</button>
                        </form>
                    </div>
                    @error('scanToken') <p role="alert" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p> @enderror
                    @error('card') <p role="alert" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p> @enderror
                    @if ($selectedProfileId)
                        <p class="mt-4 rounded-xl bg-[#eaf2e0] px-4 py-3 text-sm font-bold text-[#2a5b3e] dark:bg-emerald-800/30 dark:text-emerald-100">القائد: {{ $selectedProfileName }}</p>
                    @endif
                @else
                    <p class="mt-4 text-xs leading-6 text-[#718677] dark:text-[#adc2b3]">الدفع النقدي متاح مباشرة. اسم الضيف ورقم الهاتف اختياريان.</p>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2 md:grid-cols-1 xl:grid-cols-2">
                        <div><label for="guest-name" class="block text-xs font-bold">اسم الضيف · اختياري</label><input id="guest-name" wire:model="guestName" type="text" maxlength="255" autocomplete="off" class="mt-1 w-full rounded-xl border border-[#d5dfd3] bg-[#f7f9f5] px-3 py-2.5 text-sm dark:border-white/15 dark:bg-[#0d1c19]" /></div>
                        <div><label for="guest-phone" class="block text-xs font-bold">رقم الهاتف · اختياري</label><input id="guest-phone" wire:model="guestPhone" type="tel" dir="ltr" maxlength="32" autocomplete="off" class="mt-1 w-full rounded-xl border border-[#d5dfd3] bg-[#f7f9f5] px-3 py-2.5 text-sm dark:border-white/15 dark:bg-[#0d1c19]" /></div>
                    </div>
                    @error('guestPhone') <p role="alert" class="mt-2 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p> @enderror
                @endif
            </div>

            <div class="rounded-2xl border border-[#dee5d9] bg-white p-5 shadow-sm dark:border-white/10 dark:bg-[#142621] sm:p-6">
                <div class="mb-4 flex items-center gap-3"><span class="grid size-8 place-items-center rounded-full bg-[#dcecc7] text-sm font-black text-[#28533c]">٢</span><h2 class="font-heading text-xl font-black">المراجعة والدفع</h2></div>
                <p class="mb-4 text-sm leading-6 text-[#687d70] dark:text-[#afc0b3]">راجع الأصناف والسعر النهائي وطريقة الدفع قبل تأكيد الطلب.</p>
                <button type="button" wire:click="review" wire:loading.attr="disabled" class="min-h-14 w-full rounded-xl bg-[#204d3a] px-4 text-base font-black text-white transition hover:bg-[#2c674c] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#204d3a] disabled:opacity-60">مراجعة الطلب والدفع</button>
                @error('payment') <p role="alert" class="mt-3 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p> @enderror
                @error('wallet') <p role="alert" class="mt-3 text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p> @enderror
            </div>
        </section>
    </div>

    <flux:modal name="pos-review" wire:model.self="showReviewModal" class="w-full max-w-xl">
        @if ($reviewed)
            <div class="space-y-5 text-right">
                <div>
                    <p class="text-xs font-bold text-[#718677] dark:text-[#adc2b3]">الخطوة الأخيرة</p>
                    <flux:heading size="xl">مراجعة الطلب</flux:heading>
                    <p class="mt-2 text-sm text-[#687d70] dark:text-[#afc0b3]">{{ $buyerType === 'minor' ? 'القائد: '.$selectedProfileName : ($guestName !== '' ? 'الضيف: '.$guestName : 'طلب ضيف') }} · {{ $paymentMethod === 'cash' ? 'دفع نقدي' : 'محفظة القائد' }}</p>
                </div>
                <div class="max-h-64 divide-y divide-[#e8ece6] overflow-y-auto rounded-xl bg-[#f7f9f5] px-4 dark:divide-white/10 dark:bg-white/5">
                    @foreach ($reviewLines as $line)
                        <div wire:key="pos-review-{{ $loop->index }}" class="flex justify-between gap-3 py-3 text-sm"><span>{{ $line['name'] }} × {{ $line['quantity'] }}</span><span class="whitespace-nowrap font-semibold" dir="ltr">{{ number_format($line['total_baisa'] / 1000, 3) }}</span></div>
                    @endforeach
                </div>
                <p class="flex items-baseline justify-between border-t-2 border-[#d6e4d4] pt-4 font-heading text-2xl font-black dark:border-white/15"><span>الإجمالي المستحق</span><span dir="ltr">{{ number_format($reviewedTotalBaisa / 1000, 3) }} ر.ع</span></p>
                <form wire:submit="pay" class="space-y-4">
                    @if ($paymentMethod === 'cash')
                        <div>
                            <div class="flex flex-wrap items-center justify-between gap-2"><label for="cash-received" class="text-sm font-bold">المبلغ النقدي المستلم (ر.ع)</label><button type="button" wire:click="useExactCash" class="min-h-11 rounded-lg border border-[#cad8c8] px-3 text-sm font-bold text-[#204d3a] dark:border-white/20 dark:text-emerald-200">استلام المبلغ كاملًا</button></div>
                            <input id="cash-received" wire:model="cashReceived" type="text" inputmode="decimal" dir="ltr" autocomplete="off" placeholder="0.000" class="mt-2 min-h-14 w-full rounded-xl border border-[#d5dfd3] bg-[#f7f9f5] px-4 text-center text-2xl outline-none focus:border-[#5a9a70] dark:border-white/15 dark:bg-[#0d1c19]" />
                        </div>
                    @endif
                    @foreach (['card', 'cashReceived', 'total', 'payment', 'wallet', 'order', 'inventory', 'guestPhone'] as $errorKey)
                        @error($errorKey) <p role="alert" class="text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p> @enderror
                    @endforeach
                    <div class="flex flex-col-reverse gap-3 sm:flex-row">
                        <flux:modal.close><button type="button" class="min-h-14 w-full rounded-xl border border-[#cad8c8] px-4 text-base font-bold dark:border-white/20">تعديل الطلب</button></flux:modal.close>
                        <button type="submit" wire:loading.attr="disabled" class="min-h-14 flex-[1.5] rounded-xl bg-[#204d3a] px-4 text-base font-black text-white transition hover:bg-[#2c674c] disabled:opacity-60">{{ $paymentMethod === 'cash' ? 'تأكيد استلام النقد والطلب' : 'تأكيد الخصم والدفع' }}</button>
                    </div>
                </form>
            </div>
        @endif
    </flux:modal>
</div>
