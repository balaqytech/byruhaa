@extends('layouts.public', ['title' => 'دخول حساب القاصر'])

@section('content')
<section class="mx-auto w-full max-w-md px-4 py-12 sm:py-16" dir="rtl">
    <header class="mb-8 text-center">
        <h1 class="font-heading text-3xl font-semibold text-emerald-950 dark:text-white">دخول حساب القاصر</h1>
        <p class="mt-3 text-sm leading-7 text-emerald-900/70 dark:text-white/70">طلباتك ومحفظتك في مكان واحد. استخدم رمز العضوية وكلمة المرور بعد تفعيل حسابك.</p>
    </header>
    <div class="rounded-2xl border border-emerald-900/10 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-zinc-900 sm:p-8">
        @if (session('success'))
            <p role="status" class="mb-5 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-900 dark:bg-emerald-300/10 dark:text-emerald-100">{{ session('success') }}</p>
        @endif
        @if ($errors->any())
            <p role="alert" class="mb-5 rounded-lg bg-rose-50 p-3 text-sm text-rose-800 dark:bg-rose-300/10 dark:text-rose-200">{{ __($errors->first()) }}</p>
        @endif
        <form method="POST" action="{{ route('minor.login.store') }}" class="grid gap-5">
            @csrf
            <flux:input name="member_code" :error:message="$errors->has('member_code') ? __($errors->first('member_code')) : null" label="رمز العضوية" :value="old('member_code')" placeholder="BRH-XXXXXXXX" autocomplete="username" dir="ltr" required autofocus />
            <flux:input name="password" :error:message="$errors->has('password') ? __($errors->first('password')) : null" label="كلمة المرور" type="password" autocomplete="current-password" dir="ltr" required viewable />
            <flux:button type="submit" variant="primary" class="w-full">دخول</flux:button>
        </form>
        <p class="mt-5 text-sm leading-7 text-emerald-900/70 dark:text-white/70">لم تفعّل حسابك بعد؟ اطلب رابط التفعيل من وليّ الأمر.</p>
    </div>
    <a href="{{ route('login') }}" class="mt-6 block text-center text-sm font-semibold text-emerald-700 underline dark:text-emerald-300">دخول وليّ الأمر</a>
</section>
@endsection
