<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold tracking-[0.12em] text-[#718778] dark:text-[#a7b9a9]">نقطة البيع / متابعة الطلبات</p>
            <h1 class="mt-2 font-heading text-3xl font-black sm:text-4xl">طلبات نقطة البيع</h1>
            <p class="mt-2 text-sm text-[#687d70] dark:text-[#afc0b3]">تظهر طلباتك اليوم. ابحث برقم الطلب الكامل للوصول إلى طلب آخر مخوّل أو طلب سابق لإرجاع نقدي.</p>
        </div>
        <a href="{{ route('cashier.terminal') }}" class="inline-flex min-h-12 items-center rounded-xl bg-[#204d3a] px-5 text-sm font-bold text-white hover:bg-[#2c674c]">طلب جديد</a>
    </div>

    @if ($refundOrderId !== null)
        <form wire:submit="requestCashRefund" class="space-y-4 rounded-2xl border border-amber-300 bg-amber-50 p-5 text-[#20372b] dark:border-amber-700 dark:bg-[#302819] dark:text-white">
            <div>
                <h2 class="text-lg font-black">إرجاع مبلغ نقدي — تسجيل الطلب</h2>
                <p class="text-sm">سيُسجل طلب الإرجاع أولًا. لن يُثبت الصرف أو قيد Ledger حتى تؤكد تسليم النقد.</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="refund-amount" class="text-sm font-bold">المبلغ OMR</label>
                    <input id="refund-amount" wire:model="refundAmountOmr" type="text" inputmode="decimal" dir="ltr" class="mt-1 min-h-12 w-full rounded-xl border border-amber-300 bg-white px-4 dark:bg-[#142621]" required>
                    @error('refundAmountOmr') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="refund-reason" class="text-sm font-bold">سبب الإرجاع</label>
                    <input id="refund-reason" wire:model="refundReason" type="text" maxlength="255" class="mt-1 min-h-12 w-full rounded-xl border border-amber-300 bg-white px-4 dark:bg-[#142621]" required>
                    @error('refundReason') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>
            </div>
            @error('refund') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
            @error('amount') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
            <div class="flex gap-3">
                <button type="submit" wire:loading.attr="disabled" class="min-h-12 rounded-xl bg-[#204d3a] px-5 text-sm font-bold text-white disabled:opacity-50">تسجيل طلب الإرجاع</button>
                <button type="button" wire:click="$set('refundOrderId', null)" class="min-h-12 rounded-xl border border-amber-400 px-5 text-sm font-bold">إلغاء</button>
            </div>
        </form>
    @endif

    @if ($payoutRefundId !== null && $payoutRefund !== null)
        <form wire:submit="confirmCashPayout" class="space-y-4 rounded-2xl border border-emerald-300 bg-emerald-50 p-5 text-[#20372b] dark:border-emerald-700 dark:bg-[#172d23] dark:text-white">
            <div>
                <h2 class="text-lg font-black">إرجاع مبلغ نقدي — تأكيد التسليم</h2>
                <p class="text-sm">الطلب {{ $payoutRefund->reference }} · مبلغ الصرف {{ number_format($payoutRefund->amount_baisa / 1000, 3) }} OMR</p>
            </div>
            <div>
                <label for="cash-refund-receipt" class="text-sm font-bold">مرجع إيصال تسليم النقد</label>
                <input id="cash-refund-receipt" wire:model="receiptReference" type="text" maxlength="100" class="mt-1 min-h-12 w-full rounded-xl border border-emerald-300 bg-white px-4 dark:bg-[#142621]" required>
                @error('receiptReference') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <label class="flex items-center gap-3 text-sm font-bold"><input wire:model="cashHandedOver" type="checkbox" class="size-5"> أؤكد أنني سلّمت المبلغ نقدًا للمستلم</label>
            @error('cashHandedOver') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
            @error('refund') <p class="text-sm text-red-700">{{ $message }}</p> @enderror
            <div class="flex gap-3">
                <button type="submit" wire:loading.attr="disabled" class="min-h-12 rounded-xl bg-[#204d3a] px-5 text-sm font-bold text-white disabled:opacity-50">تأكيد تسليم النقد</button>
                <button type="button" wire:click="$set('payoutRefundId', null)" class="min-h-12 rounded-xl border border-emerald-400 px-5 text-sm font-bold">لاحقًا</button>
            </div>
        </form>
    @endif

    <div class="rounded-2xl border border-[#dee5d9] bg-white p-4 shadow-sm dark:border-white/10 dark:bg-[#142621] sm:p-5">
        <label for="cashier-order-search" class="block text-sm font-bold">ابحث برقم الطلب الكامل</label>
        <input id="cashier-order-search" wire:model.live.debounce.300ms="search" type="search" dir="ltr" autocomplete="off" maxlength="64" class="mt-2 min-h-12 w-full rounded-xl border border-[#d5dfd3] bg-[#f7f9f5] px-4 text-base outline-none focus:border-[#5a9a70] dark:border-white/15 dark:bg-[#0d1c19]" placeholder="مثال: POS-..." />
    </div>

    <div class="grid gap-3">
        @forelse ($orders as $order)
            <article wire:key="cashier-order-{{ $order->id }}" class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[#dee5d9] bg-white p-4 shadow-sm dark:border-white/10 dark:bg-[#142621] sm:p-5">
                <div class="min-w-0">
                    <p class="font-mono text-lg font-black" dir="ltr">{{ $order->reference }}</p>
                    <p class="mt-1 text-sm text-[#687d70] dark:text-[#afc0b3]">{{ $order->customer_name }} · <time datetime="{{ $order->created_at->toIso8601String() }}">{{ $order->created_at->format('Y-m-d H:i') }}</time></p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <span class="rounded-full bg-[#eaf1e5] px-3 py-1.5 text-xs font-bold text-[#31553f] dark:bg-white/10 dark:text-emerald-200">{{ __('admin.store.order_statuses.'.$order->status->getValue()) }}</span>
                    <span class="whitespace-nowrap text-sm font-bold" dir="ltr">{{ number_format($order->total_baisa / 1000, 3) }} ر.ع</span>
                    @if ($cashierOrderAccess->canReprint($cashier, $order))
                        <a href="{{ route('cashier.orders.receipt', $order) }}" target="_blank" rel="noopener" class="inline-flex min-h-12 items-center rounded-xl border border-[#204d3a] px-4 text-sm font-bold text-[#204d3a] hover:bg-[#eaf2e5] dark:border-emerald-300 dark:text-emerald-200">إعادة طباعة الإيصال</a>
                    @endif
                    @if ($cashierOrderAccess->canViewCashRefund($cashier, $order))
                        @php
                            $refundedBaisa = $order->refundRequests->where('status', 'paid')->sum('amount_baisa');
                            $reservedBaisa = $order->refundRequests->where('status', '!=', 'paid')->sum('amount_baisa');
                        @endphp
                        <div class="text-xs leading-5 text-[#687d70] dark:text-[#afc0b3]">المدفوع {{ number_format($order->total_baisa / 1000, 3) }} · المسترد {{ number_format($refundedBaisa / 1000, 3) }} · المتبقي المتاح {{ number_format(($order->total_baisa - $refundedBaisa - $reservedBaisa) / 1000, 3) }} OMR</div>
                        @if ($cashierOrderAccess->canRefund($cashier, $order))
                            <button type="button" wire:click="openCashRefund({{ $order->id }})" class="min-h-12 rounded-xl border border-amber-500 px-4 text-sm font-bold text-amber-800 dark:text-amber-200">إرجاع مبلغ نقدي</button>
                        @endif
                        @foreach ($order->refundRequests->where('status', 'approved') as $approvedRefund)
                            <button type="button" wire:key="cash-payout-{{ $approvedRefund->id }}" wire:click="openCashPayout({{ $approvedRefund->id }})" class="min-h-12 rounded-xl border border-emerald-500 px-4 text-sm font-bold text-emerald-800 dark:text-emerald-200">تأكيد صرف {{ $approvedRefund->reference }}</button>
                        @endforeach
                        @foreach ($order->refundRequests->where('status', 'paid') as $paidRefund)
                            <a href="{{ route('cashier.orders.refunds.receipt', [$order, $paidRefund]) }}" target="_blank" rel="noopener" class="min-h-12 rounded-xl border border-[#204d3a] px-4 py-3 text-sm font-bold text-[#204d3a] dark:text-emerald-200">إيصال الإرجاع {{ $paidRefund->reference }}</a>
                        @endforeach
                    @endif
                </div>
            </article>
        @empty
            <p class="rounded-2xl border border-dashed border-[#d2dfcf] px-6 py-12 text-center text-sm text-[#819486] dark:border-white/15 dark:text-[#a8bbae]">{{ $search !== '' ? 'لم يُعثر على طلب بهذا الرقم ضمن الطلبات المسموح لك بعرضها.' : 'لم تسجّل طلبات بعد اليوم.' }}</p>
        @endforelse
    </div>

    {{ $orders->links() }}
</div>
