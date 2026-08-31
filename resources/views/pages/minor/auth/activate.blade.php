@extends('layouts.public', ['title' => 'تفعيل حساب القاصر'])

@section('content')
<section class="mx-auto flex w-full max-w-md flex-col gap-6 px-4 py-16" dir="rtl">
    <div><h1 class="text-2xl font-semibold text-emerald-950 dark:text-white">تفعيل حساب القاصر</h1><p class="mt-2 text-sm text-emerald-900/70 dark:text-white/70">{{ $minorProfile->familyMember->name }} · <span dir="ltr">{{ $minorProfile->member_code }}</span></p></div>
    <form method="POST" action="{{ URL::temporarySignedRoute('minor.activate.store', now()->addDay(), ['minorProfile' => $minorProfile->id, 'token' => $token]) }}" class="flex flex-col gap-4 rounded-2xl border border-emerald-900/10 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/5">@csrf<input type="hidden" name="token" value="{{ $token }}"><label class="flex flex-col gap-2 text-sm"><span>كلمة المرور</span><input type="password" name="password" required class="rounded-lg border-emerald-900/20 dark:border-white/10 dark:bg-white/5" dir="ltr"></label><label class="flex flex-col gap-2 text-sm"><span>تأكيد كلمة المرور</span><input type="password" name="password_confirmation" required class="rounded-lg border-emerald-900/20 dark:border-white/10 dark:bg-white/5" dir="ltr"></label><button class="rounded-lg bg-emerald-700 px-4 py-2 font-semibold text-white hover:bg-emerald-800">تفعيل الحساب</button></form>
</section>
@endsection
