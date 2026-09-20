<x-layouts::app title="محفظة القاصر">
<section class="flex flex-col gap-6" dir="rtl">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('customer.minor-profiles.index') }}" class="text-sm text-emerald-700 dark:text-emerald-300">العودة إلى الحسابات</a>
            <h1 class="mt-2 text-2xl font-semibold text-emerald-950 dark:text-white">محفظة {{ $minorProfile->familyMember->name }}</h1>
            <p class="mt-2 text-sm text-emerald-900/70 dark:text-white/70">الرصيد المتاح للدفع من حساب القاصر.</p>
        </div>
        <div class="rounded-2xl border border-emerald-900/10 bg-white px-6 py-4 text-left shadow-sm dark:border-white/10 dark:bg-white/5" dir="ltr">
            <p class="text-xs text-emerald-900/60 dark:text-white/60">الرصيد</p>
            <p class="mt-1 text-3xl font-semibold text-emerald-800 dark:text-emerald-200">{{ number_format($wallet->balance_baisa / 1000, 3) }} {{ $wallet->currency }}</p>
        </div>
    </div>

    @if (session('wallet_status'))
        <div role="status" class="rounded-xl border border-sky-300/50 bg-sky-50 px-4 py-3 text-sm text-sky-950 dark:bg-sky-300/10 dark:text-sky-100">حالة عملية الشحن: {{ __('admin_wallets.top_up_statuses.'.session('wallet_status')) }}</div>
    @endif

    @if ($errors->any())
        <div role="alert" class="rounded-xl border border-rose-300/50 bg-rose-50 px-4 py-3 text-sm text-rose-900 dark:bg-rose-300/10 dark:text-rose-100">{{ __($errors->first()) }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,22rem)_1fr]">
        <form method="POST" action="{{ route('customer.minor-profiles.wallet.top-up', $minorProfile) }}" class="flex flex-col gap-4 rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
            @csrf
            <div>
                <h2 class="text-lg font-semibold text-emerald-950 dark:text-white">شحن المحفظة</h2>
                <p class="mt-1 text-sm text-emerald-900/70 dark:text-white/70">سيتم تحويلك إلى صفحة الدفع الآمنة لإتمام الشحن.</p>
            </div>
            <flux:input name="amount_omr" :error:message="$errors->has('amount_omr') ? __($errors->first('amount_omr')) : null" label="المبلغ بالريال العماني" type="number" inputmode="decimal" required min="0.100" max="100000" step="0.001" :value="old('amount_omr', '1.000')" dir="ltr" />
            <x-policy-links :pages="['wallet', 'refund-cancellation']" label="راجع شروط الشحن والاسترداد قبل الدفع" />
            <flux:button type="submit" variant="primary">متابعة الدفع عبر ثواني</flux:button>
        </form>

        <div class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
            <h2 class="text-lg font-semibold text-emerald-950 dark:text-white">آخر الحركات</h2>
            <div class="mt-4 divide-y divide-emerald-900/10 dark:divide-white/10">
                @forelse ($wallet->movements as $movement)
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div>
                            <p class="text-sm font-medium">{{ __('admin_wallets.types.'.$movement->type) }}</p>
                            <p class="text-xs text-emerald-900/60 dark:text-white/60">{{ $movement->created_at?->format('Y-m-d H:i') }}</p>
                        </div>
                        <p class="text-sm font-semibold {{ $movement->credit_baisa > 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }}" dir="ltr">
                            {{ $movement->credit_baisa > 0 ? '+' : '-' }}{{ number_format(($movement->credit_baisa > 0 ? $movement->credit_baisa : $movement->debit_baisa) / 1000, 3) }} {{ $wallet->currency }}
                        </p>
                    </div>
                @empty
                    <p class="py-6 text-sm text-emerald-900/70 dark:text-white/70">لا توجد حركات بعد.</p>
                @endforelse
            </div>
        </div>
    </div>
</section>
</x-layouts::app>
