<x-layouts::app title="حسابات القاصرين">
<section class="flex flex-col gap-6" dir="rtl">
    <div>
        <h1 class="text-2xl font-semibold text-emerald-950 dark:text-white">حسابات القاصرين</h1>
        <p class="mt-2 text-sm text-emerald-900/70 dark:text-white/70">أنشئ حسابًا مرتبطًا بأحد أفراد الأسرة بعد التحقق من وليّ الأمر.</p>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-300/50 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:bg-emerald-300/10 dark:text-emerald-100">{{ session('success') }}</div>
    @endif

    @if (session('activation_url'))
        <div class="rounded-xl border border-sky-300/50 bg-sky-50 px-4 py-3 text-sm text-sky-950 dark:bg-sky-300/10 dark:text-sky-100">
            <p>رابط التفعيل صالح لمدة 24 ساعة:</p>
            <input readonly value="{{ session('activation_url') }}" class="mt-2 w-full rounded-lg border border-sky-300/60 bg-white px-3 py-2 text-left text-xs dark:border-white/10 dark:bg-white/5" dir="ltr">
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('customer.minor-profiles.store') }}" class="flex flex-col gap-4 rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
            @csrf
            <h2 class="text-lg font-semibold text-emerald-950 dark:text-white">إنشاء حساب</h2>
            <label class="flex flex-col gap-2 text-sm"><span>فرد الأسرة الموجود</span><select name="family_member_id" class="rounded-lg border-emerald-900/20 dark:border-white/10 dark:bg-white/5"><option value="">إنشاء فرد جديد</option>@foreach ($familyMembers as $familyMember)<option value="{{ $familyMember->id }}" @selected(old('family_member_id') == $familyMember->id) @disabled($familyMember->minorProfile !== null)>{{ $familyMember->name }}{{ $familyMember->minorProfile ? ' — لديه حساب' : '' }}</option>@endforeach</select></label>
            <label class="flex flex-col gap-2 text-sm"><span>اسم القاصر عند إنشاء فرد جديد</span><input name="name" value="{{ old('name') }}" class="rounded-lg border-emerald-900/20 dark:border-white/10 dark:bg-white/5"></label>
            <label class="flex flex-col gap-2 text-sm"><span>تاريخ الميلاد</span><input type="date" name="birth_date" value="{{ old('birth_date') }}" class="rounded-lg border-emerald-900/20 dark:border-white/10 dark:bg-white/5"></label>
            <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800">إنشاء وإرسال رمز التحقق</button>
            @error('family_member_id')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
            @error('birth_date')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
        </form>

        <div class="flex flex-col gap-3 rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
            <h2 class="text-lg font-semibold text-emerald-950 dark:text-white">الملفات الحالية</h2>
            @forelse ($profiles as $profile)
                <div class="rounded-xl border border-emerald-900/10 p-4 dark:border-white/10">
                    <div class="flex items-start justify-between gap-3"><div><p class="font-semibold">{{ $profile->familyMember->name }}</p><p class="text-xs text-emerald-900/60 dark:text-white/60" dir="ltr">{{ $profile->member_code }}</p></div><span class="rounded-full bg-emerald-100 px-2 py-1 text-xs text-emerald-800 dark:bg-emerald-300/10 dark:text-emerald-100">{{ $profile->status->value }}</span></div>
                    @if ($profile->status->value === 'pending_guardian_verification')
                        <form method="POST" action="{{ route('customer.minor-profiles.verify', $profile) }}" class="mt-3 flex gap-2">@csrf<input name="code" inputmode="numeric" maxlength="6" placeholder="رمز التحقق" class="min-w-0 flex-1 rounded-lg border-emerald-900/20 dark:border-white/10 dark:bg-white/5"><button class="rounded-lg bg-emerald-700 px-3 py-2 text-sm text-white">تحقق</button></form>
                    @endif
                    <div class="mt-3 flex flex-wrap gap-2 text-xs">
                        @if ($profile->status->value === 'active')<form method="POST" action="{{ route('customer.minor-profiles.direct-payment', $profile) }}">@csrf<button class="rounded-lg border border-emerald-900/20 px-3 py-2 dark:border-white/10">{{ $profile->direct_payment_enabled ? 'إيقاف الدفع المباشر' : 'السماح بالدفع المباشر' }}</button></form>@endif
                        @if ($profile->status->value === 'suspended')<form method="POST" action="{{ route('customer.minor-profiles.resume', $profile) }}">@csrf<button class="rounded-lg border border-emerald-900/20 px-3 py-2 dark:border-white/10">إعادة التفعيل</button></form>@elseif ($profile->status->value === 'active')<form method="POST" action="{{ route('customer.minor-profiles.suspend', $profile) }}">@csrf<button class="rounded-lg border border-amber-300 px-3 py-2 text-amber-800">تعليق</button></form>@endif
                        @if (! in_array($profile->status->value, ['deletion_requested', 'invalidated'], true))<form method="POST" action="{{ route('customer.minor-profiles.delete-request', $profile) }}">@csrf<button class="rounded-lg border border-rose-300 px-3 py-2 text-rose-700">طلب الحذف</button></form>@endif
                    </div>
                </div>
            @empty
                <p class="rounded-xl border border-dashed border-emerald-900/20 p-6 text-sm text-emerald-900/70 dark:border-white/10 dark:text-white/70">لا توجد حسابات قاصرين بعد.</p>
            @endforelse
        </div>
    </div>
</section>
</x-layouts::app>
