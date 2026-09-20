@extends('layouts.public', ['title' => 'تفعيل حساب القاصر'])

@section('content')
<section class="mx-auto w-full max-w-md px-4 py-12 sm:py-16" dir="rtl">
    <header class="mb-8 text-center">
        <h1 class="font-heading text-3xl font-semibold text-emerald-950 dark:text-white">تفعيل حساب القاصر</h1>
        <p class="mt-3 text-sm text-emerald-900/70 dark:text-white/70">مرحبًا {{ $minorProfile->familyMember->name }}، اختر كلمة مرور لحسابك.</p>
        <p class="mt-2 text-sm">رمز العضوية: <bdi class="font-mono font-semibold">{{ $minorProfile->member_code }}</bdi></p>
    </header>
    <form method="POST" action="{{ URL::temporarySignedRoute('minor.activate.store', now()->addDay(), ['minorProfile' => $minorProfile->id, 'token' => $token]) }}" class="grid gap-5 rounded-2xl border border-emerald-900/10 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-zinc-900 sm:p-8">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        @if ($errors->any())
            <p role="alert" class="rounded-lg bg-rose-50 p-3 text-sm text-rose-800 dark:bg-rose-300/10 dark:text-rose-200">{{ __($errors->first()) }}</p>
        @endif
        <flux:input name="password" :error:message="$errors->has('password') ? __($errors->first('password')) : null" label="كلمة المرور" type="password" autocomplete="new-password" dir="ltr" required viewable />
        <flux:input name="password_confirmation" :error:message="$errors->has('password_confirmation') ? __($errors->first('password_confirmation')) : null" label="تأكيد كلمة المرور" type="password" autocomplete="new-password" dir="ltr" required viewable />
        <x-policy-links :pages="['student-accounts', 'privacy']" label="تعرف على استخدام حسابك وخصوصيتك" />
        <flux:button type="submit" variant="primary" class="w-full">تفعيل الحساب</flux:button>
    </form>
</section>
@endsection
