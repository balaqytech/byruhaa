@extends('layouts.public', ['title' => 'طلباتي'])

@section('content')
<section class="mx-auto w-full max-w-6xl space-y-7 px-4 py-8 sm:px-6 sm:py-12 lg:px-8" dir="rtl">
    <header class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">حساب {{ $profile->familyMember->name }}</p>
            <h1 class="mt-2 font-heading text-3xl font-semibold text-emerald-950 dark:text-white sm:text-4xl">طلباتي</h1>
            <p class="mt-3 text-sm leading-7 text-emerald-900/65 dark:text-white/65">تابع حالة كل طلب وافتح تفاصيل الدفع والاستلام.</p>
        </div>
        <a href="{{ route('coffee') }}" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-sm bg-emerald-800 px-5 text-sm font-semibold text-white transition hover:bg-emerald-900 active:translate-y-px dark:bg-emerald-300 dark:text-emerald-950 dark:hover:bg-emerald-200"><x-hugeicon name="sparkles" class="text-lg" />اطلب من قهوة بيرحاء</a>
    </header>

    <x-minor-panel-nav />

    <div class="grid grid-cols-3 divide-x divide-x-reverse divide-emerald-900/10 border-y border-emerald-900/10 py-5 dark:divide-white/10 dark:border-white/10">
        <div class="px-3 text-center sm:px-6"><p class="text-2xl font-semibold text-emerald-950 dark:text-white">{{ $ordersCount }}</p><p class="mt-1 text-xs text-emerald-900/55 dark:text-white/55">كل الطلبات</p></div>
        <div class="px-3 text-center sm:px-6"><p class="text-2xl font-semibold text-amber-700 dark:text-amber-300">{{ $activeOrdersCount }}</p><p class="mt-1 text-xs text-emerald-900/55 dark:text-white/55">قيد المتابعة</p></div>
        <div class="px-3 text-center sm:px-6"><p class="text-2xl font-semibold text-emerald-700 dark:text-emerald-300">{{ $completedOrdersCount }}</p><p class="mt-1 text-xs text-emerald-900/55 dark:text-white/55">مكتملة</p></div>
    </div>

    <div class="divide-y divide-emerald-900/10 border-y border-emerald-900/10 dark:divide-white/10 dark:border-white/10">
        @forelse ($orders as $order)
            <a href="{{ route('minor.orders.show', $order->payment_token) }}" class="group grid gap-4 py-5 transition hover:bg-emerald-50/60 dark:hover:bg-white/5 sm:grid-cols-[1fr_auto] sm:items-center sm:px-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-3"><bdi class="break-all font-semibold text-emerald-950 dark:text-white">{{ $order->reference }}</bdi><flux:badge size="sm" :color="match ($order->status->getValue()) { 'pending_payment', 'refund_pending' => 'amber', 'cancelled', 'rejected', 'expired' => 'red', default => 'green' }">{{ $order->status->label() }}</flux:badge></div>
                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-emerald-900/55 dark:text-white/55"><time datetime="{{ $order->created_at->toAtomString() }}">{{ $order->created_at->format('Y-m-d · H:i') }}</time><span>{{ $order->payment_method === 'wallet' ? 'الدفع من المحفظة' : 'الدفع عبر ثواني' }}</span></div>
                </div>
                <div class="flex items-center justify-between gap-5 sm:justify-end"><p class="text-lg font-semibold text-emerald-950 dark:text-white"><x-money :amount-baisa="$order->total_baisa" :currency="$order->currency" /></p><span class="inline-flex size-10 items-center justify-center rounded-full border border-emerald-900/10 text-emerald-700 transition group-hover:border-emerald-700/30 group-hover:bg-white dark:border-white/10 dark:text-emerald-300 dark:group-hover:bg-white/10"><x-hugeicon name="arrow-left-02" class="text-lg" /></span></div>
            </a>
        @empty
            <div class="grid justify-items-center gap-4 py-16 text-center"><span class="inline-flex size-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-300/10 dark:text-emerald-200"><x-hugeicon name="invoice-03" class="text-2xl" /></span><div><h2 class="font-semibold text-emerald-950 dark:text-white">لا توجد طلبات بعد</h2><p class="mt-2 text-sm text-emerald-900/60 dark:text-white/60">عندما تطلب من قائمة القهوة ستظهر طلباتك هنا.</p></div><a href="{{ route('coffee') }}" class="text-sm font-semibold text-emerald-700 hover:underline dark:text-emerald-300">تصفح قائمة القهوة</a></div>
        @endforelse
    </div>

    <div>{{ $orders->links() }}</div>
</section>
@endsection
