@extends('layouts.public', ['title' => 'دخول حساب القاصر'])

@section('content')
<section class="mx-auto flex w-full max-w-md flex-col gap-6 px-4 py-16" dir="rtl">
    <div><h1 class="text-2xl font-semibold text-emerald-950 dark:text-white">دخول حساب القاصر</h1><p class="mt-2 text-sm text-emerald-900/70 dark:text-white/70">استخدم رمز الدخول الذي سلّمه لك وليّ الأمر.</p></div>
    @if (session('success'))<div class="rounded-xl border border-emerald-300/50 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:bg-emerald-300/10 dark:text-emerald-100">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="rounded-xl border border-rose-300/50 bg-rose-50 px-4 py-3 text-sm text-rose-900 dark:bg-rose-300/10 dark:text-rose-100">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('minor.login.store') }}" class="flex flex-col gap-4 rounded-2xl border border-emerald-900/10 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/5">@csrf<label class="flex flex-col gap-2 text-sm"><span>رمز الدخول</span><input name="member_code" value="{{ old('member_code') }}" required class="rounded-lg border-emerald-900/20 uppercase dark:border-white/10 dark:bg-white/5" dir="ltr"></label><label class="flex flex-col gap-2 text-sm"><span>كلمة المرور</span><input type="password" name="password" required class="rounded-lg border-emerald-900/20 dark:border-white/10 dark:bg-white/5" dir="ltr"></label><button class="rounded-lg bg-emerald-700 px-4 py-2 font-semibold text-white hover:bg-emerald-800">دخول</button></form>
</section>
@endsection
