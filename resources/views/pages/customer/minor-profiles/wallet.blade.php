<x-layouts::app title="محفظة القائد">
<section class="flex flex-col gap-6" dir="rtl">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('customer.minor-profiles.index') }}" class="text-sm text-emerald-700 dark:text-emerald-300">العودة إلى الحسابات</a>
            <h1 class="mt-2 text-2xl font-semibold text-emerald-950 dark:text-white">محفظة {{ $minorProfile->familyMember->name }}</h1>
            <p class="mt-2 text-sm text-emerald-900/70 dark:text-white/70">الرصيد المتاح بعد خصم طلبات السحب قيد المعالجة.</p>
        </div>
        <div class="rounded-2xl border border-emerald-900/10 bg-white px-6 py-4 text-left shadow-sm dark:border-white/10 dark:bg-white/5" dir="ltr">
            <p class="text-xs text-emerald-900/60 dark:text-white/60">الرصيد المتاح</p>
            <p class="mt-1 text-3xl font-semibold text-emerald-800 dark:text-emerald-200">{{ number_format($walletSummary->availableBaisa / 1000, 3) }} {{ $wallet->currency }}</p>
            <p class="mt-1 text-xs text-emerald-900/60 dark:text-white/60">الإجمالي {{ number_format($walletSummary->balanceBaisa / 1000, 3) }} · المحجوز {{ number_format($walletSummary->heldBaisa / 1000, 3) }}</p>
        </div>
    </div>

    @if (session('wallet_status'))
        <div role="status" class="rounded-xl border border-sky-300/50 bg-sky-50 px-4 py-3 text-sm text-sky-950 dark:bg-sky-300/10 dark:text-sky-100">حالة عملية الشحن: {{ __('admin_wallets.top_up_statuses.'.session('wallet_status')) }}</div>
    @endif

    @if (session('withdrawal_status'))
        <div role="status" class="rounded-xl border border-sky-300/50 bg-sky-50 px-4 py-3 text-sm text-sky-950 dark:bg-sky-300/10 dark:text-sky-100">حالة طلب السحب: {{ session('withdrawal_status') === 'pending' ? 'قيد المراجعة' : 'أُلغي الطلب' }}</div>
    @endif

    @if (session('phone_verification_sent'))
        <div role="status" class="rounded-xl border border-sky-300/50 bg-sky-50 px-4 py-3 text-sm text-sky-950 dark:bg-sky-300/10 dark:text-sky-100">أُرسل رمز التحقق إلى رقم هاتف حسابك المسجل. أدخله أدناه قبل انتهاء صلاحيته.</div>
    @endif

    @if (session('phone_verified'))
        <div role="status" class="rounded-xl border border-emerald-300/50 bg-emerald-50 px-4 py-3 text-sm text-emerald-950 dark:bg-emerald-300/10 dark:text-emerald-100">تم توثيق رقم هاتفك. يمكنك الآن تقديم طلب السحب.</div>
    @endif

    @if ($errors->any())
        <div role="alert" class="rounded-xl border border-rose-300/50 bg-rose-50 px-4 py-3 text-sm text-rose-900 dark:bg-rose-300/10 dark:text-rose-100">{{ __($errors->first()) }}</div>
    @endif

    @if (! $guardianPhoneVerified)
        <div class="rounded-2xl border border-amber-300/50 bg-amber-50 p-5 dark:bg-amber-300/10">
            <h2 class="text-lg font-semibold text-amber-950 dark:text-amber-100">توثيق رقم هاتف وليّ الأمر مطلوب للسحب</h2>
            <p class="mt-1 text-sm text-amber-900/80 dark:text-amber-100/80">سنرسل رمز تحقق إلى رقم هاتف حسابك المسجل. بعد توثيقه يمكنك طلب Withdrawal من الرصيد المتاح.</p>
            <div class="mt-4 flex flex-wrap items-end gap-4">
                <form method="POST" action="{{ route('customer.phone-verification.send') }}">
                    @csrf
                    <flux:button type="submit" variant="primary">إرسال رمز التحقق</flux:button>
                </form>
                <form method="POST" action="{{ route('customer.phone-verification.verify') }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <flux:input name="code" label="رمز التحقق المكوّن من 6 أرقام" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required dir="ltr" />
                    <flux:button type="submit">تأكيد الرمز</flux:button>
                </form>
            </div>
        </div>
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

    <div class="grid gap-6 lg:grid-cols-[minmax(0,22rem)_1fr]">
        <form method="POST" action="{{ route('customer.minor-profiles.wallet.withdrawals.store', $minorProfile) }}" class="flex flex-col gap-4 rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
            @csrf
            <input type="hidden" name="operation_key" value="{{ old('operation_key', $withdrawalOperationKey) }}">
            <div>
                <h2 class="text-lg font-semibold text-emerald-950 dark:text-white">طلب سحب من المحفظة</h2>
                <p class="mt-1 text-sm text-emerald-900/70 dark:text-white/70">سيُحجز المبلغ لحين مراجعة الطلب، ثم يوثق الموظف تسليمه نقدًا أو تحويله بنكيًا.</p>
            </div>
            <flux:input name="amount_omr" label="المبلغ بالريال العماني" type="number" inputmode="decimal" min="0.001" step="0.001" required :value="old('amount_omr')" dir="ltr" />
            <flux:select name="method" label="وسيلة الاستلام المفضلة" required>
                <flux:select.option value="cash">نقدًا</flux:select.option>
                <flux:select.option value="bank">تحويل بنكي</flux:select.option>
            </flux:select>
            <flux:input name="bank_name" label="اسم البنك (للتحويل البنكي)" :value="old('bank_name')" />
            <flux:input name="bank_account" label="رقم الحساب أو IBAN لولي الأمر (للتحويل البنكي)" :value="old('bank_account')" dir="ltr" />
            <x-policy-links :pages="['wallet', 'refund-cancellation']" label="راجع سياسة السحب والاسترداد" />
            <flux:button type="submit" variant="primary" :disabled="$walletSummary->availableBaisa < 1 || ! $guardianPhoneVerified">إرسال طلب السحب</flux:button>
        </form>

        <div class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
            <h2 class="text-lg font-semibold text-emerald-950 dark:text-white">طلبات السحب</h2>
            <div class="mt-4 divide-y divide-emerald-900/10 dark:divide-white/10">
                @forelse ($withdrawals as $withdrawal)
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3 text-sm">
                        <div>
                            <p class="font-medium">{{ number_format($withdrawal->amount_baisa / 1000, 3) }} OMR · {{ $withdrawal->method === 'bank' ? 'تحويل بنكي' : 'نقدًا' }}</p>
                            <p class="text-xs text-emerald-900/60 dark:text-white/60">{{ $withdrawal->reference }} · {{ $withdrawal->created_at?->format('Y-m-d H:i') }}</p>
                            @if ($withdrawal->status === 'rejected' && $withdrawal->review_note)
                                <p class="text-xs text-rose-700 dark:text-rose-300">{{ $withdrawal->review_note }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-3">
                            <span>{{ ['pending' => 'قيد المراجعة', 'approved' => 'موافق عليه', 'paying' => 'قيد الصرف', 'paid' => 'تم الصرف', 'rejected' => 'مرفوض', 'cancelled' => 'ملغى', 'manual_review' => 'مراجعة يدوية'][$withdrawal->status] ?? $withdrawal->status }}</span>
                            @if ($withdrawal->status === 'pending')
                                <form method="POST" action="{{ route('customer.minor-profiles.wallet.withdrawals.cancel', [$minorProfile, $withdrawal]) }}">
                                    @csrf
                                    <flux:button type="submit" size="sm" variant="ghost">إلغاء</flux:button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="py-6 text-sm text-emerald-900/70 dark:text-white/70">لا توجد طلبات سحب بعد.</p>
                @endforelse
            </div>
        </div>
    </div>
</section>
</x-layouts::app>
