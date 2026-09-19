@extends('layouts.public', ['title' => 'تأكيد الدفع من المحفظة'])

@section('content')
    <section class="mx-auto flex max-w-2xl flex-col gap-6" dir="rtl">
        <div>
            <h1 class="text-2xl font-semibold text-emerald-950 dark:text-white">تأكيد الدفع من المحفظة</h1>
            <p class="mt-2 text-sm text-emerald-900/70 dark:text-white/70">راجع الطلب ثم أكد الخصم من محفظة حساب الابن.</p>
        </div>

        <div class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center justify-between gap-4">
                <span class="text-sm text-emerald-900/70 dark:text-white/70">رقم الطلب</span>
                <strong dir="ltr">{{ $order->reference }}</strong>
            </div>
            <div class="mt-3 flex items-center justify-between gap-4 border-t border-emerald-900/10 pt-3 dark:border-white/10">
                <span class="text-sm text-emerald-900/70 dark:text-white/70">الإجمالي</span>
                <strong><x-money :amount-baisa="$order->total_baisa" :currency="$order->currency" /></strong>
            </div>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-rose-300/50 bg-rose-50 px-4 py-3 text-sm text-rose-900 dark:bg-rose-300/10 dark:text-rose-100">{{ __($errors->first()) }}</div>
        @endif

        <form method="POST" action="{{ route('customer.store.orders.wallet.confirm', $order->payment_token) }}">
            @csrf
            <button class="w-full rounded-xl bg-emerald-700 px-4 py-3 font-semibold text-white hover:bg-emerald-800">تأكيد الخصم والدفع</button>
        </form>
    </section>
@endsection
