<x-layouts::app title="حسابات القاصرين">
<section class="mx-auto w-full max-w-7xl space-y-6" dir="rtl">
    <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">الأسرة والحسابات</p>
            <h1 class="mt-2 text-3xl font-semibold text-emerald-950 dark:text-white">حسابات القاصرين</h1>
            <p class="mt-3 max-w-2xl text-sm leading-7 text-emerald-900/70 dark:text-white/70">أنشئ حسابًا لأحد أفراد الأسرة، ثم تحكّم في صلاحيات الدفع وتابع محفظته من هنا.</p>
        </div>
        @if (config('byruhaa.minor_accounts.enabled', true))
            <flux:button :href="route('minor.login')">دخول حساب القاصر</flux:button>
        @endif
    </header>

    @if (session('success'))
        <div role="status" class="rounded-xl border border-emerald-300/50 bg-emerald-50 p-4 text-sm text-emerald-900 dark:bg-emerald-300/10 dark:text-emerald-100">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="rounded-xl border border-rose-300/50 bg-rose-50 p-4 text-sm text-rose-900 dark:bg-rose-300/10 dark:text-rose-100">{{ __($errors->first()) }}</div>
    @endif
    @if (session('activation_url'))
        <div class="space-y-3 rounded-xl border border-sky-300/50 bg-sky-50 p-5 text-sm text-sky-950 dark:bg-sky-300/10 dark:text-sky-100">
            <h2 class="font-semibold">الخطوة التالية: تفعيل حساب الابن</h2>
            <p>شارك هذا الرابط مع الابن ليختار كلمة مروره. الرابط صالح لمدة 24 ساعة.</p>
            <flux:input label="رابط التفعيل" readonly :value="session('activation_url')" dir="ltr" copyable />
        </div>
    @endif

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <aside class="rounded-2xl border border-emerald-900/10 bg-white p-6 dark:border-white/10 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">إنشاء حساب</h2>
            <p class="mt-2 text-sm leading-7 text-emerald-900/70 dark:text-white/70">اختر فردًا موجودًا أو أضف فردًا جديدًا. سيظهر رابط التفعيل مباشرةً ليختار الابن كلمة مروره.</p>
            <form method="POST" action="{{ route('customer.minor-profiles.store') }}" x-data="{ familyMemberId: @js((string) old('family_member_id', '')) }" class="mt-5 grid gap-5">
                @csrf
                <flux:select name="family_member_id" :error:message="$errors->has('family_member_id') ? __($errors->first('family_member_id')) : null" label="فرد الأسرة" x-model="familyMemberId">
                    <option value="">إنشاء فرد جديد</option>
                    @foreach ($familyMembers as $familyMember)
                        <option value="{{ $familyMember->id }}" @selected(old('family_member_id') == $familyMember->id) @disabled($familyMember->minorProfile !== null)>{{ $familyMember->name }}{{ $familyMember->minorProfile ? ' — لديه حساب' : '' }}</option>
                    @endforeach
                </flux:select>
                <div x-cloak x-show="! familyMemberId" class="grid gap-5">
                    <flux:input name="name" :error:message="$errors->has('name') ? __($errors->first('name')) : null" label="اسم القاصر" :value="old('name')" x-bind:required="! familyMemberId" x-bind:disabled="!! familyMemberId" />
                    <flux:input name="birth_date" :error:message="$errors->has('birth_date') ? __($errors->first('birth_date')) : null" label="تاريخ الميلاد" type="date" :value="old('birth_date')" x-bind:required="! familyMemberId" x-bind:disabled="!! familyMemberId" dir="ltr" />
                </div>
                <p x-cloak x-show="familyMemberId" class="text-sm leading-7 text-emerald-900/70 dark:text-white/70">سيُستخدم الاسم وتاريخ الميلاد المسجّلان لفرد الأسرة.</p>
                <p class="text-xs leading-6 text-emerald-900/70 dark:text-white/70">بإنشاء الحساب، توافق على استخدام الابن للمتجر تحت إشرافك. تبقى صلاحيات الدفع متوقفة إلى أن تفعّلها.</p>
                <flux:checkbox
                    name="browser_notifications_consent"
                    value="1"
                    :checked="old('browser_notifications_consent')"
                    :label="config('byruhaa.minor_accounts.browser_notifications.consent_text')"
                />
                @error('browser_notifications_consent')
                    <p class="text-sm text-rose-700 dark:text-rose-300">{{ $message }}</p>
                @enderror
                <x-policy-links :pages="['student-accounts', 'privacy']" label="راجع قبل إنشاء حساب الابن" />
                <flux:button type="submit" variant="primary" class="w-full">إنشاء الحساب</flux:button>
            </form>
        </aside>

        <div class="min-w-0 space-y-4 lg:col-span-2">
            <div class="flex items-center gap-3"><h2 class="text-lg font-semibold">حسابات الأبناء</h2><flux:badge size="sm">{{ $profiles->count() }}</flux:badge></div>
            <p class="text-sm leading-7 text-emerald-900/70 dark:text-white/70">بعد التفعيل، يدخل الابن برمز العضوية الموضّح في بطاقته وكلمة مروره.</p>
            <x-policy-links :pages="['student-accounts', 'wallet', 'refund-cancellation']" label="راجع قبل تغيير صلاحيات الدفع أو طلب الحذف" />
            @forelse ($profiles as $profile)
                <article class="overflow-hidden rounded-2xl border border-emerald-900/10 bg-white dark:border-white/10 dark:bg-zinc-900">
                    <header class="flex flex-wrap items-start justify-between gap-4 bg-emerald-50/60 p-5 dark:bg-white/5 sm:p-6">
                        <div class="flex min-w-0 items-center gap-3">
                            <span aria-hidden="true" class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-xl font-semibold text-emerald-800 dark:bg-emerald-300/10 dark:text-emerald-200">{{ mb_substr($profile->familyMember->name, 0, 1) }}</span>
                            <div class="min-w-0"><h3 class="break-words text-lg font-semibold">{{ $profile->familyMember->name }}</h3><p class="mt-1 text-xs text-emerald-900/60 dark:text-white/60">رمز العضوية: <bdi class="font-mono font-semibold">{{ $profile->member_code }}</bdi></p></div>
                        </div>
                        <flux:badge size="sm" :color="match ($profile->status->value) { 'active' => 'green', 'suspended', 'invalidated' => 'red', default => 'amber' }">{{ $profile->status->label() }}</flux:badge>
                    </header>
                    <div class="space-y-5 p-5 sm:p-6">
                        @if (in_array($profile->status->value, ['pending_guardian_verification', 'pending_child_activation'], true))
                            <p class="text-sm leading-7 text-emerald-900/70 dark:text-white/70">شارك رابط التفعيل مع الابن ليختار كلمة مروره. لا يلزم إدخال رمز تحقق.</p>
                            <form method="POST" action="{{ route('customer.minor-profiles.activation-link', $profile) }}" class="space-y-3">
                                @csrf
                                <flux:button type="submit">استخراج رابط تفعيل جديد</flux:button>
                                <p class="text-xs text-emerald-900/60 dark:text-white/60">الرابط صالح لمدة 24 ساعة، ويحل محل أي رابط سابق.</p>
                            </form>
                        @elseif ($profile->status->value === 'active')
                            <div class="divide-y divide-emerald-900/10 dark:divide-white/10">
                                @if (config('byruhaa.wallets.enabled', false))
                                    <div class="flex flex-wrap items-center justify-between gap-3 pb-4">
                                        <div><h4 class="text-sm font-semibold">الدفع من المحفظة</h4><p class="mt-1 text-xs text-emerald-900/60 dark:text-white/60">{{ $profile->wallet_spending_enabled ? 'مسموح للابن بالدفع من رصيده.' : 'غير مفعّل لهذا الحساب.' }}</p></div>
                                        <div class="flex flex-wrap gap-2">
                                            <flux:button size="sm" :href="route('customer.minor-profiles.wallet', $profile)" variant="primary">المحفظة</flux:button>
                                            <form method="POST" action="{{ route('customer.minor-profiles.wallet-spending', $profile) }}">@csrf<flux:button size="sm" type="submit">{{ $profile->wallet_spending_enabled ? 'إيقاف الدفع من المحفظة' : 'السماح بالدفع من المحفظة' }}</flux:button></form>
                                        </div>
                                    </div>
                                @endif
                                <div class="flex flex-wrap items-center justify-between gap-3 pt-4">
                                    <div><h4 class="text-sm font-semibold">الدفع المباشر عبر ثواني</h4><p class="mt-1 text-xs text-emerald-900/60 dark:text-white/60">{{ $profile->direct_payment_enabled ? 'مسموح للابن بالدفع عبر بوابة ثواني.' : 'يتولى وليّ الأمر دفع هذه الطلبات.' }}</p></div>
                                    <form method="POST" action="{{ route('customer.minor-profiles.direct-payment', $profile) }}">@csrf<flux:button size="sm" type="submit">{{ $profile->direct_payment_enabled ? 'إيقاف الدفع المباشر' : 'السماح بالدفع المباشر' }}</flux:button></form>
                                </div>
                            </div>
                        @endif
                        @if (! in_array($profile->status->value, ['deletion_requested', 'invalidated'], true))
                            <div class="flex flex-wrap items-center gap-3 border-t border-emerald-900/10 pt-4 dark:border-white/10">
                                @if ($profile->status->value === 'suspended')
                                    <form method="POST" action="{{ route('customer.minor-profiles.resume', $profile) }}">@csrf<flux:button type="submit" size="sm" variant="primary">إعادة التفعيل</flux:button></form>
                                @elseif ($profile->status->value === 'active')
                                    <form method="POST" action="{{ route('customer.minor-profiles.suspend', $profile) }}">@csrf<flux:button type="submit" size="sm">تعليق الحساب</flux:button></form>
                                @endif
                                <form method="POST" action="{{ route('customer.minor-profiles.delete-request', $profile) }}">@csrf<flux:button type="submit" size="sm" variant="danger">طلب الحذف</flux:button></form>
                            </div>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-emerald-900/20 p-10 text-center dark:border-white/10"><h3 class="font-semibold">لا توجد حسابات قاصرين بعد</h3><p class="mt-3 text-sm leading-7 text-emerald-900/70 dark:text-white/70">ابدأ بإنشاء حساب لأحد أفراد الأسرة. ستظهر هنا حالته وصلاحيات الدفع الخاصة به.</p></div>
            @endforelse
        </div>
    </div>
</section>
</x-layouts::app>
