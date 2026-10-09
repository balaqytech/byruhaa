<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold tracking-[0.12em] text-[#718778] dark:text-[#a7b9a9]">نقطة البيع / متابعة الطلبات</p>
            <h1 class="mt-2 font-heading text-3xl font-black sm:text-4xl">طلبات اليوم</h1>
            <p class="mt-2 text-sm text-[#687d70] dark:text-[#afc0b3]">تظهر طلباتك اليوم. لإعادة طباعة طلب لكاشير آخر، أدخل رقم الطلب كاملًا.</p>
        </div>
        <a href="{{ route('cashier.terminal') }}" class="inline-flex min-h-12 items-center rounded-xl bg-[#204d3a] px-5 text-sm font-bold text-white hover:bg-[#2c674c]">طلب جديد</a>
    </div>

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
                    @if ($order->paid_at)
                        <a href="{{ route('cashier.orders.receipt', $order) }}" target="_blank" rel="noopener" class="inline-flex min-h-12 items-center rounded-xl border border-[#204d3a] px-4 text-sm font-bold text-[#204d3a] hover:bg-[#eaf2e5] dark:border-emerald-300 dark:text-emerald-200">إعادة طباعة الإيصال</a>
                    @endif
                </div>
            </article>
        @empty
            <p class="rounded-2xl border border-dashed border-[#d2dfcf] px-6 py-12 text-center text-sm text-[#819486] dark:border-white/15 dark:text-[#a8bbae]">{{ $search !== '' ? 'لم يُعثر على طلب بهذا الرقم ضمن طلبات اليوم المسموح لك بعرضها.' : 'لم تسجّل طلبات بعد اليوم.' }}</p>
        @endforelse
    </div>

    {{ $orders->links() }}
</div>
