<!DOCTYPE html>
<html lang="ar" dir="rtl" class="scroll-smooth">
    <head>
        @include('partials.head')

        @isset($metaDescription)
            <meta name="description" content="{{ $metaDescription }}">
            <meta property="og:description" content="{{ $metaDescription }}">
        @endisset

        <meta property="og:title" content="{{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta name="theme-color" content="#009060">

        @isset($metaImage)
            <meta property="og:image" content="{{ $metaImage }}">
            <meta name="twitter:card" content="summary_large_image">
        @endisset

        @vite('resources/js/public-site.js')
    </head>
    <body class="min-h-screen overflow-x-hidden bg-[#fbf9f1] text-[#123329] antialiased selection:bg-[#e0a800]/35 selection:text-[#123329] dark:bg-[#07120f] dark:text-[#f7f1df]">
        @php
            $navigationLinks = [
                ['label' => 'الرئيسية', 'route' => 'home', 'active' => 'home', 'icon' => 'home-01'],
                ['label' => 'الفعاليات', 'route' => 'events.index', 'active' => 'events.*', 'icon' => 'calendar-03'],
                ['label' => 'المدونة', 'route' => 'blog.index', 'active' => 'blog.*', 'icon' => 'book-open-text'],
                ['label' => 'عن المنتجع', 'route' => 'about', 'active' => 'about', 'icon' => 'information-circle'],
                ['label' => 'تواصل معنا', 'route' => 'contact', 'active' => 'contact', 'icon' => 'mail-01'],
            ];

            $accountRoute = auth('customer')->check() ? 'customer.dashboard' : 'login';
            $bottomNavigationLinks = [
                ['label' => 'الرئيسية', 'route' => 'home', 'active' => 'home', 'icon' => 'home-01'],
                ['label' => 'الفعاليات', 'route' => 'events.index', 'active' => 'events.*', 'icon' => 'calendar-03'],
                ['label' => 'المدونة', 'route' => 'blog.index', 'active' => 'blog.*', 'icon' => 'book-open-text'],
                ['label' => 'حسابي', 'route' => $accountRoute, 'active' => 'customer.*', 'icon' => 'user-circle'],
            ];

            $themeOptions = [
                ['label' => 'فاتح', 'theme' => 'light', 'icon' => 'sun-01'],
                ['label' => 'داكن', 'theme' => 'dark', 'icon' => 'moon-02'],
                ['label' => 'النظام', 'theme' => 'system', 'icon' => 'computer'],
            ];
        @endphp

        <div class="pointer-events-none fixed inset-0 -z-10 bg-[#fbf9f1] dark:bg-[#07120f]"></div>
        <div class="pointer-events-none fixed inset-0 -z-10 bg-[linear-gradient(135deg,rgba(0,144,96,0.08)_0%,rgba(255,255,255,0.88)_38%,rgba(24,152,176,0.05)_66%,rgba(224,168,0,0.08)_100%)] dark:bg-[linear-gradient(135deg,rgba(0,144,96,0.16)_0%,rgba(7,18,15,0.92)_44%,rgba(224,24,56,0.08)_100%)]"></div>
        <div class="pointer-events-none fixed inset-0 -z-10 opacity-45 bg-[radial-gradient(circle_at_1px_1px,rgba(0,144,96,0.16)_1px,transparent_0)] [background-size:28px_28px] dark:opacity-20 dark:bg-[radial-gradient(circle_at_1px_1px,rgba(224,168,0,0.22)_1px,transparent_0)]"></div>

        <header data-public-header class="fixed inset-x-0 top-0 z-50 border-b border-[#009060]/12 bg-[#fbf9f1]/86 shadow-sm shadow-[#123329]/5 backdrop-blur-xl dark:border-white/10 dark:bg-[#07120f]/84 dark:shadow-black/20">
            <input id="public-navigation-toggle" type="checkbox" class="peer sr-only">

            <div class="mx-auto flex min-h-20 w-full max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="group inline-flex items-center gap-3" aria-label="منتجع بيرحاء">
                    <span class="flex size-12 items-center justify-center overflow-hidden rounded-sm border border-[#009060]/16 bg-white/70 shadow-sm shadow-[#123329]/5 dark:border-white/10 dark:bg-white/10">
                        <img src="{{ asset('logo.png') }}" alt="منتجع بيرحاء" class="h-10 w-auto object-contain">
                    </span>
                    <span class="grid leading-none">
                        <span class="font-heading text-xl font-bold text-[#123329] dark:text-[#f7f1df]">منتجع بيرحاء</span>
                        <span class="mt-1 text-xs text-[#009060] dark:text-[#e0a800]">BYRUHAA</span>
                    </span>
                </a>

                <nav class="ms-auto hidden items-center gap-1 text-sm font-medium text-[#123329]/68 lg:flex dark:text-[#f7f1df]/70" aria-label="التنقل الرئيسي">
                    @foreach ($navigationLinks as $link)
                        <a href="{{ route($link['route']) }}" class="inline-flex items-center gap-2 rounded-sm px-3 py-2 transition hover:bg-[#009060]/8 hover:text-[#123329] dark:hover:bg-white/10 dark:hover:text-white {{ request()->routeIs($link['active']) ? 'bg-[#009060]/10 text-[#009060] dark:bg-[#e0a800]/12 dark:text-[#e0a800]' : '' }}">
                            <x-hugeicon :name="$link['icon']" class="text-base" />
                            <span>{{ $link['label'] }}</span>
                        </a>
                    @endforeach
                </nav>

                <div class="hidden items-center gap-3 lg:flex">
                    <div class="inline-flex items-center gap-1 rounded-sm border border-[#009060]/14 bg-white/54 p-1 dark:border-white/10 dark:bg-white/5" aria-label="تبديل المظهر">
                        @foreach ($themeOptions as $option)
                            <button type="button" data-theme-toggle data-theme="{{ $option['theme'] }}" class="inline-flex size-9 items-center justify-center rounded-sm text-[#123329]/62 transition hover:bg-[#009060]/8 hover:text-[#009060] data-[active=true]:bg-[#009060] data-[active=true]:text-white dark:text-[#f7f1df]/68 dark:hover:bg-white/10 dark:hover:text-[#e0a800] dark:data-[active=true]:bg-[#e0a800] dark:data-[active=true]:text-[#07120f]" title="{{ $option['label'] }}" aria-label="{{ $option['label'] }}">
                                <x-hugeicon :name="$option['icon']" class="text-lg" />
                            </button>
                        @endforeach
                    </div>

                    @if (Route::has($accountRoute))
                        <a href="{{ route($accountRoute) }}" class="inline-flex min-h-10 items-center gap-2 rounded-sm border border-[#009060]/18 px-4 py-2 text-sm font-semibold text-[#009060] transition hover:border-[#009060]/35 hover:bg-[#009060]/8 dark:border-[#e0a800]/24 dark:text-[#e0a800] dark:hover:bg-[#e0a800]/10">
                            <x-hugeicon :name="auth('customer')->check() ? 'dashboard-square-01' : 'login-03'" class="text-lg" />
                            <span>{{ auth('customer')->check() ? __('ui.labels.dashboard') : __('ui.actions.log_in') }}</span>
                        </a>
                    @endif
                </div>

                <label for="public-navigation-toggle" class="ms-auto inline-flex size-11 cursor-pointer items-center justify-center rounded-sm border border-[#009060]/18 text-[#123329] transition hover:border-[#009060]/35 hover:bg-[#009060]/8 lg:hidden dark:border-white/10 dark:text-[#f7f1df] dark:hover:bg-white/10" aria-label="فتح القائمة">
                    <x-hugeicon name="menu-01" class="text-2xl" />
                </label>
            </div>

            <nav class="hidden border-t border-[#009060]/12 bg-[#fbf9f1]/96 px-4 py-4 text-sm font-medium text-[#123329]/74 peer-checked:grid lg:peer-checked:hidden dark:border-white/10 dark:bg-[#07120f]/96 dark:text-[#f7f1df]/72" aria-label="التنقل عبر الجوال">
                <div class="mx-auto grid w-full max-w-7xl gap-2">
                    @foreach ($navigationLinks as $link)
                        <a href="{{ route($link['route']) }}" class="flex items-center gap-3 rounded-sm px-3 py-3 transition hover:bg-[#009060]/8 hover:text-[#123329] dark:hover:bg-white/10 dark:hover:text-white {{ request()->routeIs($link['active']) ? 'bg-[#009060]/10 text-[#009060] dark:bg-[#e0a800]/12 dark:text-[#e0a800]' : '' }}">
                            <x-hugeicon :name="$link['icon']" class="text-lg" />
                            <span>{{ $link['label'] }}</span>
                        </a>
                    @endforeach

                    <div class="mt-2 flex items-center gap-2 border-t border-[#009060]/12 pt-3 dark:border-white/10">
                        @foreach ($themeOptions as $option)
                            <button type="button" data-theme-toggle data-theme="{{ $option['theme'] }}" class="inline-flex min-h-10 flex-1 items-center justify-center gap-2 rounded-sm border border-[#009060]/14 px-3 text-xs font-semibold text-[#123329]/68 transition data-[active=true]:border-[#009060] data-[active=true]:bg-[#009060] data-[active=true]:text-white dark:border-white/10 dark:text-[#f7f1df]/68 dark:data-[active=true]:border-[#e0a800] dark:data-[active=true]:bg-[#e0a800] dark:data-[active=true]:text-[#07120f]">
                                <x-hugeicon :name="$option['icon']" class="text-base" />
                                <span>{{ $option['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </nav>
        </header>

        <main class="relative min-h-screen pb-24 pt-20 lg:pb-0">
            @yield('content')
        </main>

        <nav class="fixed inset-x-0 bottom-0 z-50 border-t border-[#009060]/12 bg-white/94 px-3 pb-[max(env(safe-area-inset-bottom),0.75rem)] pt-2 shadow-[0_-10px_30px_rgba(18,51,41,0.08)] backdrop-blur-xl lg:hidden dark:border-white/10 dark:bg-[#07120f]/94 dark:shadow-black/30" aria-label="التنقل السفلي">
            <div class="mx-auto grid max-w-md grid-cols-4 gap-1">
                @foreach ($bottomNavigationLinks as $link)
                    @php
                        $isAccount = $link['label'] === 'حسابي';
                        $isActive = $isAccount
                            ? request()->routeIs('customer.*') || request()->routeIs('login', 'register', 'password.*')
                            : request()->routeIs($link['active']);
                    @endphp

                    @if (Route::has($link['route']))
                        <a href="{{ route($link['route']) }}" class="relative flex min-h-14 flex-col items-center justify-center gap-1 rounded-sm text-xs font-semibold transition {{ $isActive ? 'text-[#009060] dark:text-[#e0a800]' : 'text-[#123329]/60 hover:bg-[#009060]/8 hover:text-[#009060] dark:text-[#f7f1df]/58 dark:hover:bg-white/10 dark:hover:text-[#e0a800]' }}">
                            <x-hugeicon :name="$link['icon']" class="text-2xl" />
                            <span>{{ $link['label'] }}</span>
                            @if ($isActive)
                                <span class="absolute top-1 h-1.5 w-1.5 rounded-full bg-[#e0a800]"></span>
                            @endif
                        </a>
                    @endif
                @endforeach
            </div>
        </nav>

        <footer class="border-t border-[#009060]/12 bg-white/56 dark:border-white/10 dark:bg-white/5">
            <div class="mx-auto grid w-full max-w-7xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-[1fr_auto] lg:px-8">
                <div class="max-w-xl">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('logo.png') }}" alt="منتجع بيرحاء" class="h-10 w-auto rounded-sm bg-white/70 p-1 dark:bg-white/10">
                        <div>
                            <p class="font-heading text-xl font-bold text-[#123329] dark:text-[#f7f1df]">منتجع بيرحاء</p>
                            <p class="mt-1 text-sm text-[#123329]/58 dark:text-[#f7f1df]/62">تجارب سياحية وتعليمية بروح عُمانية فاخرة.</p>
                        </div>
                    </div>
                </div>

                <nav class="flex flex-wrap items-center gap-x-5 gap-y-3 text-sm text-[#123329]/62 dark:text-[#f7f1df]/62" aria-label="روابط التذييل">
                    @foreach ($navigationLinks as $link)
                        <a href="{{ route($link['route']) }}" class="transition hover:text-[#009060] dark:hover:text-[#e0a800]">{{ $link['label'] }}</a>
                    @endforeach
                </nav>
            </div>
        </footer>
    </body>
</html>
