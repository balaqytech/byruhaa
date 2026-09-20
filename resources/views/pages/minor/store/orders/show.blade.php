@extends('layouts.public', ['title' => 'تفاصيل الطلب'])

@section('content')
<section class="mx-auto w-full max-w-6xl space-y-6 px-4 py-8 sm:px-6 sm:py-12 lg:px-8" dir="rtl">
    <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <a href="{{ route('minor.orders.index') }}" class="text-sm font-semibold text-emerald-700 dark:text-emerald-300">← العودة إلى حسابي</a>
            <h1 class="mt-3 font-heading text-3xl font-semibold">تفاصيل الطلب</h1>
            <p class="mt-2 text-sm text-emerald-900/70 dark:text-white/70"><bdi>{{ $order->reference }}</bdi></p>
        </div>
        <flux:badge :color="match ($order->status->getValue()) { 'pending_payment', 'refund_pending' => 'amber', 'cancelled', 'rejected', 'expired' => 'red', default => 'green' }">{{ $order->status->label() }}</flux:badge>
    </header>
    @if ($errors->any())
        <div role="alert" class="rounded-xl border border-rose-300/50 bg-rose-50 p-4 text-sm text-rose-900 dark:bg-rose-300/10 dark:text-rose-100">{{ __($errors->first()) }}</div>
    @endif
    <div class="grid items-start gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-emerald-900/10 bg-white p-6 dark:border-white/10 dark:bg-zinc-900 lg:col-span-2">
            <h2 class="text-lg font-semibold">الأصناف</h2>
            <div class="mt-5 divide-y divide-emerald-900/10 dark:divide-white/10">
                @foreach ($order->items as $item)
                    <div class="flex items-center justify-between gap-4 py-4">
                        <div class="min-w-0"><p class="font-medium">{{ $item->product_name }} · {{ $item->option_name }}</p><p class="mt-1 text-sm text-emerald-900/60 dark:text-white/60">الكمية: {{ $item->quantity }}</p></div>
                        <span class="shrink-0"><x-money :amount-baisa="$item->line_total_baisa" :currency="$item->currency" /></span>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 flex items-center justify-between border-t border-emerald-900/10 pt-5 text-lg font-bold dark:border-white/10"><span>الإجمالي</span><x-money :amount-baisa="$order->total_baisa" :currency="$order->currency" /></div>
            <p class="mt-3 text-xs text-emerald-900/60 dark:text-white/60">الاستلام من المقهى · {{ $order->payment_method === 'wallet' ? 'الدفع من المحفظة' : 'الدفع عبر ثواني' }}</p>
        </div>
        <aside class="rounded-2xl border border-emerald-900/10 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
            <h2 class="mb-3 text-lg font-semibold">{{ $order->status->getValue() === 'pending_payment' ? 'إتمام الدفع' : 'حالة الطلب' }}</h2>
            <x-policy-links :pages="$order->payment_method === 'wallet' ? ['wallet', 'pickup', 'refund-cancellation'] : ['terms', 'pickup', 'refund-cancellation']" label="سياسات الدفع والاستلام" class="mb-4" />
            @if ($order->status->getValue() === 'pending_payment')
                @if ($paymentBlockReason)
                    <p class="text-sm leading-7 text-emerald-900/70 dark:text-white/70">{{ __($paymentBlockReason) }}</p>
                @else
                    <p class="text-sm leading-7 text-emerald-900/70 dark:text-white/70">{{ $order->payment_method === 'wallet' ? 'سيتم خصم قيمة الطلب من محفظتك بعد التحقق من الرصيد.' : 'ستنتقل إلى ثواني لإتمام دفع هذا الطلب.' }}</p>
                    <form method="POST" action="{{ route('minor.orders.payment', $order->payment_token) }}" class="mt-5">@csrf<flux:button type="submit" variant="primary" class="w-full">{{ $order->payment_method === 'wallet' ? 'الدفع من المحفظة' : 'الدفع عبر ثواني' }}</flux:button></form>
                @endif
            @else
                <p class="text-sm leading-7 text-emerald-900/70 dark:text-white/70">يمكنك متابعة تغيّرات الحالة في سجل الطلب أدناه.</p>
            @endif
            <a href="{{ route('minor.orders.index') }}#wallet" class="mt-5 block text-sm font-semibold text-emerald-700 underline dark:text-emerald-300">العودة إلى حسابي والمحفظة</a>
        </aside>
    </div>
    <section aria-labelledby="order-history-title" class="min-w-0 rounded-2xl border border-emerald-900/10 bg-white p-5 dark:border-white/10 dark:bg-zinc-900 sm:p-6">
        <h2 id="order-history-title" class="text-lg font-semibold">سجل تغيّرات حالة الطلب</h2>
        <p class="mt-2 text-sm text-emerald-900/70 dark:text-white/70">التحديثات مرتبة من الأحدث إلى الأقدم.</p>
        <div class="mt-5 overflow-x-auto">
            <table class="w-full text-start text-sm">
                <caption class="sr-only">سجل تغيّرات حالة الطلب {{ $order->reference }}</caption>
                <thead class="border-b border-emerald-900/10 bg-emerald-50/60 dark:border-white/10 dark:bg-white/5">
                    <tr>
                        <th scope="col" class="whitespace-nowrap px-3 py-3 text-start font-semibold">التاريخ والوقت</th>
                        <th scope="col" class="whitespace-nowrap px-3 py-3 text-start font-semibold">الحالة السابقة</th>
                        <th scope="col" class="whitespace-nowrap px-3 py-3 text-start font-semibold">الحالة الجديدة</th>
                        <th scope="col" class="px-3 py-3 text-start font-semibold">الملاحظة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-emerald-900/10 dark:divide-white/10">
                    @forelse ($order->statusHistory->sortBy([['created_at', 'desc'], ['id', 'desc']]) as $history)
                        <tr>
                            <td class="whitespace-nowrap px-3 py-4"><time datetime="{{ $history->created_at->toIso8601String() }}" dir="ltr">{{ $history->created_at->format('Y-m-d H:i') }}</time></td>
                            <td class="whitespace-nowrap px-3 py-4 text-emerald-900/70 dark:text-white/70">{{ $history->from_status ? \App\Modules\Store\States\Order\OrderState::make($history->from_status, $order)->label() : 'إنشاء الطلب' }}</td>
                            <td class="whitespace-nowrap px-3 py-4 font-medium">{{ \App\Modules\Store\States\Order\OrderState::make($history->to_status, $order)->label() }}</td>
                            <td class="min-w-48 px-3 py-4 leading-7 text-emerald-900/70 dark:text-white/70">{{ filled($history->note) ? __($history->note) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-8 text-center text-emerald-900/70 dark:text-white/70">لا توجد تغيّرات مسجّلة لهذا الطلب بعد.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</section>
@endsection
