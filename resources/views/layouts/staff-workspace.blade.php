<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        @include('partials.head', ['title' => match ($workspace) { 'cashier' => 'نقطة البيع', 'barista' => 'شاشة الباريستا', default => 'نقطة التسليم' }])
        @if ($workspace === 'cashier')
            @vite('resources/js/pos-qr-scanner.js')
        @endif
        <meta name="theme-color" content="#102b27">
    </head>
    <body class="min-h-screen bg-[#f4f3ec] text-[#19352f] antialiased selection:bg-[#c9e9a5] selection:text-[#17372c] dark:bg-[#0c1917] dark:text-[#eef3e9]">
        <div class="min-h-screen">
            <header class="border-b border-white/10 bg-[#102b27] text-white">
                <div class="mx-auto flex w-full max-w-[1600px] flex-wrap items-center justify-between gap-4 px-5 py-4 sm:px-8">
                    <div class="flex items-center gap-4">
                        <span class="grid size-11 place-items-center rounded-2xl border border-white/20 bg-white/10 font-heading text-2xl font-black text-[#dcf2b1]">ب</span>
                        <div>
                            <p class="text-xs font-semibold tracking-[0.15em] text-[#b9d6c6]">بِيرُحاء · مساحة الفريق</p>
                            <p class="font-heading text-xl font-black">{{ match ($workspace) { 'cashier' => 'نقطة البيع', 'barista' => 'شاشة الباريستا', default => 'نقطة التسليم' } }}</p>
                        </div>
                    </div>

                    @php($staff = auth($workspace)->user())
                    @if ($staff)
                        <div class="flex items-center gap-3">
                            @if ($workspace === 'cashier')
                                <nav aria-label="تنقل الكاشير" class="flex items-center gap-1 rounded-xl border border-white/15 p-1 text-sm font-semibold">
                                    <a href="{{ route('cashier.terminal') }}" @if (request()->routeIs('cashier.terminal')) aria-current="page" @endif class="rounded-lg px-3 py-2 {{ request()->routeIs('cashier.terminal') ? 'bg-white/20' : 'hover:bg-white/10' }}">بيع</a>
                                    <a href="{{ route('cashier.orders') }}" @if (request()->routeIs('cashier.orders')) aria-current="page" @endif class="rounded-lg px-3 py-2 {{ request()->routeIs('cashier.orders') ? 'bg-white/20' : 'hover:bg-white/10' }}">طلبات اليوم</a>
                                </nav>
                            @endif
                            <div class="hidden text-left sm:block">
                                <p class="text-xs text-[#b9d6c6]">مرحبًا</p>
                                <p class="text-sm font-semibold">{{ $staff->name }}</p>
                            </div>
                            <form method="POST" action="{{ route($workspace.'.logout') }}">
                                @csrf
                                <button type="submit" class="rounded-xl border border-white/20 px-4 py-2.5 text-sm font-semibold transition hover:bg-white/10 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#dcf2b1]">تسجيل الخروج</button>
                            </form>
                        </div>
                    @endif
                </div>
            </header>

            <main class="mx-auto w-full max-w-[1600px] px-5 py-8 sm:px-8 sm:py-10">
                @hasSection('content')
                    @yield('content')
                @else
                    {{ $slot }}
                @endif
            </main>
        </div>
        @fluxScripts
    </body>
</html>
