@extends('layouts.public', ['title' => 'حركات المحفظة'])

@section('content')
<section class="mx-auto w-full max-w-5xl space-y-7 px-4 py-8 sm:px-6 sm:py-12 lg:px-8" dir="rtl">
    <header class="grid gap-6 sm:grid-cols-[1fr_auto] sm:items-end">
        <div>
            <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">حساب {{ $profile->familyMember->name }}</p>
            <h1 class="mt-2 font-heading text-3xl font-semibold text-emerald-950 dark:text-white sm:text-4xl">حركات المحفظة</h1>
            <p class="mt-3 text-sm leading-7 text-emerald-900/65 dark:text-white/65">سجل زمني لعمليات الشحن والشراء والاسترداد.</p>
        </div>
        <div class="text-start sm:text-end">
            <p class="text-xs text-emerald-900/55 dark:text-white/55">الرصيد المتاح</p>
            <p class="mt-1 text-2xl font-semibold text-emerald-950 dark:text-white"><x-money :amount-baisa="max(0, $wallet->balance_baisa - $reservedBalance)" :currency="$wallet->currency" /></p>
        </div>
    </header>

    <x-minor-panel-nav />

    <div class="divide-y divide-emerald-900/10 border-y border-emerald-900/10 dark:divide-white/10 dark:border-white/10">
        @forelse ($movements as $movement)
            <article class="grid grid-cols-[auto_1fr_auto] items-center gap-3 py-5 sm:gap-5 sm:px-4">
                <span class="inline-flex size-11 items-center justify-center rounded-full {{ $movement->credit_baisa > 0 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-300/10 dark:text-emerald-200' : 'bg-rose-100 text-rose-800 dark:bg-rose-300/10 dark:text-rose-200' }}"><x-hugeicon :name="$movement->credit_baisa > 0 ? 'add-01' : 'wallet-02'" class="text-xl" /></span>
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-emerald-950 dark:text-white">{{ __('admin_wallets.types.'.$movement->type) }}</h2>
                    <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-emerald-900/50 dark:text-white/50">
                        <time datetime="{{ $movement->created_at?->toAtomString() }}"><bdi>{{ $movement->created_at?->format('Y-m-d · H:i') }}</bdi></time>
                        @if (filled($movement->order_reference))<span>الطلب: <bdi>{{ $movement->order_reference }}</bdi></span>@endif
                        <span>الرصيد بعدها: {{ number_format($movement->balance_after_baisa / 1000, 3) }} {{ $wallet->currency }}</span>
                    </div>
                </div>
                <span dir="ltr" class="shrink-0 text-sm font-semibold {{ $movement->credit_baisa > 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}">{{ $movement->credit_baisa > 0 ? '+' : '−' }}{{ number_format(($movement->credit_baisa > 0 ? $movement->credit_baisa : $movement->debit_baisa) / 1000, 3) }} {{ $wallet->currency }}</span>
            </article>
        @empty
            <div class="grid justify-items-center gap-4 py-16 text-center"><span class="inline-flex size-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-300/10 dark:text-emerald-200"><x-hugeicon name="wallet-02" class="text-2xl" /></span><div><h2 class="font-semibold text-emerald-950 dark:text-white">لا توجد حركات بعد</h2><p class="mt-2 text-sm text-emerald-900/60 dark:text-white/60">ستظهر هنا عمليات شحن المحفظة والشراء والاسترداد.</p></div></div>
        @endforelse
    </div>

    <div>{{ $movements->links() }}</div>
</section>
@endsection
