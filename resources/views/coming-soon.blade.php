<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#07120f">
    <title>قريبًا — {{ config('app.name') }}</title>
    @vite('resources/css/app.css')
</head>
<body class="min-h-dvh bg-[#07120f] text-[#f7f1df] antialiased">
    <main class="relative isolate grid min-h-dvh place-items-center overflow-hidden px-5 py-16">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(circle_at_15%_15%,rgba(0,144,96,0.28),transparent_34%),radial-gradient(circle_at_85%_80%,rgba(224,168,0,0.18),transparent_30%)]"></div>
        <section class="w-full max-w-2xl text-center">
            <img src="{{ asset('logo.png') }}" alt="منتجع بيرحاء" class="mx-auto h-28 w-auto object-contain sm:h-36">
            <p class="mt-10 text-sm font-bold tracking-[0.22em] text-[#e8bd42]">نُعِدُّ لكم شيئًا أجمل</p>
            <h1 class="mt-5 font-heading text-5xl font-bold leading-tight sm:text-7xl">الموقع قريبًا</h1>
            <p class="mx-auto mt-6 max-w-xl text-lg leading-9 text-[#f7f1df]/70 sm:text-xl">
                نعمل الآن على تجهيز تجربة بيرحاء الجديدة. سنعود إليكم قريبًا بموقع يليق بالمكان وبرامجه.
            </p>
            <div class="mx-auto mt-10 h-px w-24 bg-[#e8bd42]/55"></div>
            <p class="mt-8 text-sm text-[#f7f1df]/50">منتجع بيرحاء · إبراء · سلطنة عُمان</p>
        </section>
    </main>
</body>
</html>
