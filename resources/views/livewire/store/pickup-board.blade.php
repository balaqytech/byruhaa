<div wire:poll.10s class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold tracking-[0.12em] text-[#718778] dark:text-[#a7b9a9]">بِيرُحاء / نقطة التسليم</p>
            <h1 class="mt-2 font-heading text-3xl font-black sm:text-4xl">طلبات جاهزة للاستلام</h1>
            <p class="mt-2 text-sm text-[#687d70] dark:text-[#afc0b3]">تحقق من رقم الطلب مع المستلم، ثم أكّد التسليم. تتجدد القائمة كل ١٠ ثوانٍ.</p>
        </div>
        <span class="rounded-full bg-[#dcece9] px-4 py-2 text-sm font-bold text-[#22645b] dark:bg-teal-800/30 dark:text-teal-200">{{ $orders->total() }} جاهزة</span>
    </div>

    @error('status')
        <p role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800 dark:border-rose-800 dark:bg-rose-900/20 dark:text-rose-100">{{ $message }}</p>
    @enderror

    <div class="rounded-2xl border border-[#dee5d9] bg-white p-4 shadow-sm dark:border-white/10 dark:bg-[#142621] sm:p-5">
        <label for="pickup-order-search" class="block text-sm font-bold">ابحث برقم الطلب الكامل</label>
        <input id="pickup-order-search" wire:model.live.debounce.300ms="search" type="search" dir="ltr" autocomplete="off" maxlength="64" class="mt-2 min-h-12 w-full rounded-xl border border-[#d5dfd3] bg-[#f7f9f5] px-4 text-base outline-none focus:border-[#5a9a70] dark:border-white/15 dark:bg-[#0d1c19]" placeholder="رقم الطلب" />
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($orders as $order)
            <article wire:key="pickup-order-{{ $order->id }}" class="flex flex-col rounded-2xl border border-[#dce4d9] bg-white p-5 shadow-sm dark:border-white/10 dark:bg-[#142621]">
                <div class="flex items-start justify-between gap-3">
                    <div><p class="text-xs font-bold text-[#718677] dark:text-[#adc2b3]">رقم الطلب</p><h2 class="mt-1 font-mono text-xl font-black" dir="ltr">{{ $order->reference }}</h2></div>
                    <time datetime="{{ $order->created_at->toIso8601String() }}" class="text-xs text-[#718677] dark:text-[#adc2b3]">{{ $order->created_at->format('Y-m-d H:i') }}</time>
                </div>
                <p class="mt-4 text-sm font-bold">{{ $order->recipient_name ?: $order->customer_name }}</p>
                @if ($order->pickup_at)
                    <p class="mt-2 text-xs text-[#607b69] dark:text-[#adc2b3]">موعد الاستلام: <time datetime="{{ $order->pickup_at->toIso8601String() }}">{{ $order->pickup_at->format('Y-m-d H:i') }}</time></p>
                @endif
                <ul class="mt-4 flex-1 space-y-2 border-t border-[#e9eee7] pt-3 text-sm dark:border-white/10">
                    @foreach ($order->items as $item)
                        <li wire:key="pickup-item-{{ $item->id }}">{{ $item->quantity }} × {{ $item->product_name }} @if ($item->option_name) · {{ $item->option_name }} @endif</li>
                    @endforeach
                </ul>
                @if ($canComplete)
                    <button type="button" wire:click="completeOrder({{ $order->id }})" wire:confirm="هل استلم العميل هذا الطلب بالفعل؟" wire:loading.attr="disabled" wire:target="completeOrder({{ $order->id }})" class="mt-5 min-h-12 w-full rounded-xl bg-[#204d3a] px-4 text-base font-black text-white transition hover:bg-[#2c674c] disabled:opacity-60">تأكيد تسليم الطلب</button>
                @endif
            </article>
        @empty
            <p class="rounded-2xl border border-dashed border-[#d2dfcf] px-6 py-12 text-center text-sm text-[#819486] dark:border-white/15 dark:text-[#a8bbae] md:col-span-2 xl:col-span-3">لا توجد طلبات جاهزة للتسليم حاليًا.</p>
        @endforelse
    </div>

    {{ $orders->links() }}
</div>
