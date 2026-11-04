@extends('layouts.staff-workspace', ['workspace' => $workspace])

@section('content')
    <div class="mx-auto grid max-w-5xl overflow-hidden rounded-[2rem] border border-[#dce4d8] bg-white shadow-[0_22px_70px_-45px_rgba(16,43,39,0.6)] dark:border-white/10 dark:bg-[#142621] lg:grid-cols-[1fr_1.05fr]">
        <div class="flex min-h-72 flex-col justify-between bg-[#dcecc7] p-8 text-[#173c32] sm:p-12 dark:bg-[#1d4034] dark:text-[#e6f3de]">
            <span class="w-fit rounded-full border border-current/20 px-4 py-1.5 text-xs font-bold">{{ match ($workspace) { 'cashier' => 'واجهة الكاشير', 'barista' => 'واجهة الباريستا', default => 'واجهة التسليم' } }}</span>
            <div>
                <p class="mb-3 text-sm font-semibold">مساحة عمل مستقلة</p>
                <h1 class="max-w-md font-heading text-4xl font-black leading-tight sm:text-5xl">{{ match ($workspace) { 'cashier' => 'بيع واضح، وخطوات دفع آمنة.', 'barista' => 'كل طلب أمامك، في وقته.', default => 'سلّم الطلب بثقة.' } }}</h1>
                <p class="mt-5 max-w-md text-sm leading-7 opacity-80">{{ match ($workspace) { 'cashier' => 'اختر المنتجات، امسح بطاقة القائد، راجع الإجمالي ثم أكّد الدفع برمز الشراء.', 'barista' => 'تابع الطلبات المؤكدة وقيد التحضير والجاهزة للاستلام من شاشة واحدة.', default => 'تحقق من رقم الطلب والمستلم، ثم أكّد التسليم بعد استلامه.' } }}</p>
            </div>
        </div>

        <div class="flex flex-col justify-center p-8 sm:p-12">
            <p class="text-xs font-bold tracking-[0.12em] text-[#5b786b] dark:text-[#aac8b7]">دخول الفريق</p>
            <h2 class="mt-2 font-heading text-3xl font-black">{{ match ($workspace) { 'cashier' => 'دخول الكاشير', 'barista' => 'دخول الباريستا', default => 'دخول مسؤول التسليم' } }}</h2>
            <p class="mt-2 text-sm text-[#697d73] dark:text-[#b6c7bc]">استخدم حساب الفريق المخصص لك.</p>

            @if ($errors->any())
                <p role="alert" class="mt-6 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:bg-rose-400/10 dark:text-rose-200">{{ $errors->first() }}</p>
            @endif

            <form method="POST" action="{{ route($workspace.'.login.store') }}" class="mt-8 grid gap-5">
                @csrf
                <flux:input name="email" label="البريد الإلكتروني" type="email" :value="old('email')" autocomplete="username" dir="ltr" required autofocus />
                <flux:input name="password" label="كلمة المرور" type="password" autocomplete="current-password" dir="ltr" required viewable />
                <flux:button type="submit" variant="primary" class="w-full">دخول {{ match ($workspace) { 'cashier' => 'نقطة البيع', 'barista' => 'شاشة الطلبات', default => 'نقطة التسليم' } }}</flux:button>
            </form>
            <p class="mt-7 text-xs leading-6 text-[#789084] dark:text-[#a2b8aa]">إذا لم يكن لديك صلاحية الدخول، تواصل مع مسؤول بِيرُحاء.</p>
        </div>
    </div>
@endsection
