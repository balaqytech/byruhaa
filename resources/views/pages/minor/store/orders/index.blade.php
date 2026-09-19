@extends('layouts.public', ['title' => 'حسابي'])

@section('content')
<section class="mx-auto w-full max-w-6xl space-y-8 px-4 py-8 sm:px-6 sm:py-12 lg:px-8" dir="rtl">
    <header class="flex flex-col justify-between gap-5 sm:flex-row sm:items-center">
        <div>
            <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">حساب القاصر · <bdi>{{ $profile->member_code }}</bdi></p>
            <h1 class="mt-2 font-heading text-3xl font-semibold text-emerald-950 dark:text-white">مرحبًا، {{ $profile->familyMember->name }}</h1>
            <p class="mt-2 text-sm text-emerald-900/70 dark:text-white/70">تابع محفظتك وطلباتك من قهوة بيرحاء.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('coffee')" variant="primary">تصفح القهوة</flux:button>
            <form method="POST" action="{{ route('minor.logout') }}">@csrf<flux:button type="submit">تسجيل الخروج</flux:button></form>
        </div>
    </header>

    @if ($wallet)
        <section id="wallet" aria-labelledby="wallet-title" class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl bg-emerald-900 p-6 text-white sm:p-8">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="wallet-title" class="text-lg font-semibold">محفظتي</h2>
                    <span class="rounded-full bg-white/10 px-3 py-1 text-xs">{{ __('admin_wallets.wallet_statuses.'.$wallet->status) }}</span>
                </div>
                <p class="mt-6 text-sm text-emerald-100">الرصيد غير المحجوز</p>
                <p class="mt-2 text-4xl font-semibold"><x-money :amount-baisa="max(0, $wallet->balance_baisa - $reservedBalance)" :currency="$wallet->currency" /></p>
                @if ($reservedBalance > 0)
                    <p class="mt-3 text-sm text-emerald-100">محجوز للاسترداد: <x-money :amount-baisa="$reservedBalance" :currency="$wallet->currency" /></p>
                @endif
                <p class="mt-6 border-t border-white/20 pt-4 text-sm leading-7 text-emerald-100">
                    @if ($wallet->status !== 'active')
                        الدفع غير متاح من هذه المحفظة حاليًا. راجع وليّ الأمر.
                    @elseif (! $profile->wallet_spending_enabled)
                        الدفع من المحفظة غير مفعّل. اطلب من وليّ الأمر تفعيله من حسابه.
                    @elseif (! $profile->guardian()->hasVerifiedPhone())
                        يلزم توثيق هاتف وليّ الأمر قبل الدفع من المحفظة.
                    @else
                        يمكنك اختيار المحفظة عند إتمام الطلب. شحن الرصيد يتم من حساب وليّ الأمر.
                    @endif
                </p>
            </div>
            <div class="rounded-2xl border border-emerald-900/10 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold">آخر حركات المحفظة</h2>
                <div class="mt-4 max-h-72 overflow-y-auto divide-y divide-emerald-900/10 dark:divide-white/10">
                    @forelse ($wallet->movements as $movement)
                        <div class="flex items-center justify-between gap-3 py-3">
                            <div>
                                <p class="text-sm font-medium">{{ __('admin_wallets.types.'.$movement->type) }}</p>
                                <p class="mt-1 text-xs text-emerald-900/60 dark:text-white/60"><bdi>{{ $movement->created_at?->format('Y-m-d H:i') }}</bdi></p>
                            </div>
                            <span dir="ltr" class="shrink-0 text-sm font-semibold {{ $movement->credit_baisa > 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">{{ $movement->credit_baisa > 0 ? '+' : '−' }}{{ number_format(($movement->credit_baisa > 0 ? $movement->credit_baisa : $movement->debit_baisa) / 1000, 3) }} {{ $wallet->currency }}</span>
                        </div>
                    @empty
                        <p class="py-8 text-sm leading-7 text-emerald-900/70 dark:text-white/70">لا توجد حركات بعد. ستظهر هنا عمليات الشحن والشراء والاسترداد.</p>
                    @endforelse
                </div>
            </div>
        </section>
    @endif

    <section aria-labelledby="orders-title">
        <div class="mb-4 flex items-center gap-3"><h2 id="orders-title" class="text-xl font-semibold">طلباتي</h2><span class="rounded-full bg-emerald-100 px-3 py-1 text-xs text-emerald-900 dark:bg-white/10 dark:text-white">{{ $orders->total() }}</span></div>
        <div class="grid gap-4 sm:grid-cols-2">
            @forelse ($orders as $order)
                <a href="{{ route('minor.orders.show', $order->payment_token) }}" class="min-w-0 rounded-2xl border border-emerald-900/10 bg-white p-5 transition hover:border-emerald-600 focus-visible:outline-2 focus-visible:outline-emerald-600 dark:border-white/10 dark:bg-zinc-900">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <bdi class="break-all text-sm font-semibold">{{ $order->reference }}</bdi>
                        <flux:badge size="sm" :color="match ($order->status->getValue()) { 'pending_payment', 'refund_pending' => 'amber', 'cancelled', 'rejected', 'expired' => 'red', default => 'green' }">{{ $order->status->label() }}</flux:badge>
                    </div>
                    <p class="mt-3 text-xs text-emerald-900/60 dark:text-white/60"><bdi>{{ $order->created_at->format('Y-m-d H:i') }}</bdi></p>
                    <div class="mt-5 flex items-end justify-between gap-3 border-t border-emerald-900/10 pt-4 dark:border-white/10">
                        <p class="text-lg font-semibold"><x-money :amount-baisa="$order->total_baisa" :currency="$order->currency" /></p>
                        <span class="text-sm font-medium text-emerald-700 dark:text-emerald-300">عرض التفاصيل ←</span>
                    </div>
                </a>
            @empty
                <p class="rounded-2xl border border-dashed border-emerald-900/20 p-8 text-center text-sm sm:col-span-2 dark:border-white/10">لا توجد طلبات مرتبطة بحسابك بعد. ابدأ بتصفح قائمة القهوة.</p>
            @endforelse
        </div>
        <div class="mt-5">{{ $orders->links() }}</div>
    </section>
    @if ($notifications->isNotEmpty())
        <section class="rounded-2xl border border-emerald-900/10 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
            <h2 class="font-semibold">آخر التنبيهات</h2>
            <div class="mt-3 grid gap-2">
                @foreach ($notifications as $notification)
                    <a href="{{ data_get($notification->data, 'url', route('minor.orders.index')) }}" class="rounded-lg bg-emerald-50 px-3 py-3 text-sm text-emerald-900 dark:bg-emerald-300/10 dark:text-emerald-100"><bdi>{{ data_get($notification->data, 'reference') }}</bdi> — {{ __(data_get($notification->data, 'status_label', '')) }}</a>
                @endforeach
            </div>
        </section>
    @endif
</section>
@endsection
