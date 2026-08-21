<!DOCTYPE html>
<html lang="ar" dir="rtl" class="scroll-smooth">

<head>
    @include('partials.head')

    @isset($metaDescription)
        <meta name="description" content="{{ $metaDescription }}">
        <meta property="og:description" content="{{ $metaDescription }}">
    @endisset

    <meta property="og:title"
        content="{{ filled($title ?? null) ? $title . ' - ' . config('app.name', 'Laravel') : config('app.name', 'Laravel') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="theme-color" content="#dff6ef">

    @isset($metaImage)
        <meta property="og:image" content="{{ $metaImage }}">
        <meta name="twitter:card" content="summary_large_image">
    @endisset

    @vite('resources/js/public-site.js')
</head>

<body
    class="min-h-screen overflow-x-hidden bg-[#f6fbf8] text-[#173f35] antialiased selection:bg-[#bfe7da] selection:text-[#173f35] dark:bg-[#07120f] dark:text-[#f7f1df]">
    @php
        $publicName = $siteIdentity?->public_name ?: 'منتجع بيرحاء';
        $navigationLinks = [
            ['label' => 'الرئيسة', 'route' => 'home', 'active' => 'home', 'icon' => 'home-01'],
            ['label' => 'الفعاليات', 'route' => 'events.index', 'active' => 'events.*', 'icon' => 'calendar-03'],
            ['label' => 'قهوة بيرحاء', 'route' => 'coffee', 'active' => 'coffee', 'icon' => 'sparkles'],
            ['label' => 'المدونة', 'route' => 'blog.index', 'active' => 'blog.*', 'icon' => 'book-open-text'],
            ['label' => 'عن المنتجع', 'route' => 'about', 'active' => 'about', 'icon' => 'information-circle'],
        ];
        $footerNavigationLinks = [
            ...$navigationLinks,
            ['label' => 'تواصل معنا', 'route' => 'contact', 'active' => 'contact', 'icon' => 'mail-01'],
        ];

        $accountRoute = auth('customer')->check() ? 'customer.dashboard' : 'login';
        $bottomNavigationLinks = [
            ['label' => 'الرئيسة', 'route' => 'home', 'active' => 'home', 'icon' => 'home-01'],
            ['label' => 'الفعاليات', 'route' => 'events.index', 'active' => 'events.*', 'icon' => 'calendar-03'],
            ['label' => 'القهوة', 'route' => 'coffee', 'active' => 'coffee', 'icon' => 'sparkles'],
            ['label' => 'حسابي', 'route' => $accountRoute, 'active' => 'customer.*', 'icon' => 'user-circle'],
        ];

        $themeOptions = [
            ['label' => 'فاتح', 'theme' => 'light', 'icon' => 'sun-01'],
            ['label' => 'داكن', 'theme' => 'dark', 'icon' => 'moon-02'],
            ['label' => 'النظام', 'theme' => 'system', 'icon' => 'computer'],
        ];

        $whatsappUrl = 'https://wa.me/96874155123?text=' . rawurlencode('أرغب بالتحدث مع المساعد الذكي');
    @endphp

    <div class="pointer-events-none fixed inset-0 -z-10 bg-[#f6fbf8] dark:bg-[#07120f]"></div>
    <div
        class="pointer-events-none fixed inset-0 -z-10 bg-[linear-gradient(135deg,rgba(209,244,235,0.96)_0%,rgba(255,255,255,0.90)_36%,rgba(219,240,250,0.66)_67%,rgba(248,232,196,0.58)_100%)] dark:bg-[linear-gradient(135deg,rgba(0,144,96,0.16)_0%,rgba(7,18,15,0.92)_44%,rgba(24,152,176,0.10)_100%)]">
    </div>
    <div
        class="pointer-events-none fixed inset-0 -z-10 opacity-35 bg-[radial-gradient(circle_at_1px_1px,rgba(42,128,105,0.13)_1px,transparent_0)] [background-size:30px_30px] dark:opacity-20 dark:bg-[radial-gradient(circle_at_1px_1px,rgba(224,168,0,0.18)_1px,transparent_0)]">
    </div>

    <header data-public-header
        class="fixed inset-x-0 top-0 z-50 border-b border-[#2a8069]/12 bg-[#f6fbf8]/88 shadow-sm shadow-[#123329]/5 backdrop-blur-xl dark:border-white/10 dark:bg-[#07120f]/84 dark:shadow-black/20">
        <input id="public-navigation-toggle" type="checkbox" class="peer sr-only">

        <div class="mx-auto flex min-h-20 w-full max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="group inline-flex items-center gap-3" aria-label="{{ $publicName }}">
                <span class="flex h-20 w-auto items-center justify-center overflow-hidden">
                    <img src="{{ asset('logo-dark.png') }}" alt="{{ $publicName }}"
                        class="h-16 w-auto object-contain dark:hidden">
                    <img src="{{ asset('logo.png') }}" alt="{{ $publicName }}"
                        class="hidden h-16 w-auto object-contain dark:block">
                </span>
            </a>

            <nav class="ms-auto hidden items-center gap-1 text-sm font-medium text-[#123329]/68 lg:flex dark:text-[#f7f1df]/70"
                aria-label="التنقل الرئيسي">
                @foreach ($navigationLinks as $link)
                    <a href="{{ route($link['route']) }}"
                        class="inline-flex items-center gap-2 rounded-sm px-3 py-2 transition hover:bg-[#009060]/8 hover:text-[#123329] dark:hover:bg-white/10 dark:hover:text-white {{ request()->routeIs($link['active']) ? 'bg-[#009060]/10 text-[#009060] dark:bg-[#e0a800]/12 dark:text-[#e0a800]' : '' }}">
                        <x-hugeicon :name="$link['icon']" class="text-base" />
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="hidden items-center gap-3 lg:flex">
                <div class="inline-flex items-center gap-1 rounded-sm border border-[#009060]/14 bg-white/54 p-1 dark:border-white/10 dark:bg-white/5"
                    aria-label="تبديل المظهر">
                    @foreach ($themeOptions as $option)
                        <button type="button" data-theme-toggle data-theme="{{ $option['theme'] }}"
                            class="inline-flex size-9 items-center justify-center rounded-sm text-[#123329]/62 transition hover:bg-[#009060]/8 hover:text-[#009060] data-[active=true]:bg-[#009060] data-[active=true]:text-white dark:text-[#f7f1df]/68 dark:hover:bg-white/10 dark:hover:text-[#e0a800] dark:data-[active=true]:bg-[#e0a800] dark:data-[active=true]:text-[#07120f]"
                            title="{{ $option['label'] }}" aria-label="{{ $option['label'] }}">
                            <x-hugeicon :name="$option['icon']" class="text-lg" />
                        </button>
                    @endforeach
                </div>

                @if (Route::has($accountRoute))
                    <a href="{{ route($accountRoute) }}"
                        class="inline-flex min-h-10 items-center gap-2 rounded-sm border border-[#009060]/18 px-4 py-2 text-sm font-semibold text-[#009060] transition hover:border-[#009060]/35 hover:bg-[#009060]/8 dark:border-[#e0a800]/24 dark:text-[#e0a800] dark:hover:bg-[#e0a800]/10">
                        <x-hugeicon :name="auth('customer')->check() ? 'dashboard-square-01' : 'login-03'" class="text-lg" />
                        <span>{{ auth('customer')->check() ? __('ui.labels.dashboard') : __('ui.actions.log_in') }}</span>
                    </a>
                @endif
            </div>

            <label for="public-navigation-toggle"
                class="ms-auto inline-flex size-11 cursor-pointer items-center justify-center rounded-sm border border-[#009060]/18 text-[#123329] transition hover:border-[#009060]/35 hover:bg-[#009060]/8 lg:hidden dark:border-white/10 dark:text-[#f7f1df] dark:hover:bg-white/10"
                aria-label="فتح القائمة">
                <x-hugeicon name="menu-01" class="text-2xl" />
            </label>
        </div>

        <nav class="hidden border-t border-[#009060]/12 bg-[#fbf9f1]/96 px-4 py-4 text-sm font-medium text-[#123329]/74 peer-checked:grid lg:peer-checked:hidden dark:border-white/10 dark:bg-[#07120f]/96 dark:text-[#f7f1df]/72"
            aria-label="التنقل عبر الجوال">
            <div class="mx-auto grid w-full max-w-7xl gap-2">
                @foreach ($navigationLinks as $link)
                    <a href="{{ route($link['route']) }}"
                        class="flex items-center gap-3 rounded-sm px-3 py-3 transition hover:bg-[#009060]/8 hover:text-[#123329] dark:hover:bg-white/10 dark:hover:text-white {{ request()->routeIs($link['active']) ? 'bg-[#009060]/10 text-[#009060] dark:bg-[#e0a800]/12 dark:text-[#e0a800]' : '' }}">
                        <x-hugeicon :name="$link['icon']" class="text-lg" />
                        <span>{{ $link['label'] }}</span>
                    </a>
                @endforeach

                <div class="mt-2 flex items-center gap-2 border-t border-[#009060]/12 pt-3 dark:border-white/10">
                    @foreach ($themeOptions as $option)
                        <button type="button" data-theme-toggle data-theme="{{ $option['theme'] }}"
                            class="inline-flex min-h-10 flex-1 items-center justify-center gap-2 rounded-sm border border-[#009060]/14 px-3 text-xs font-semibold text-[#123329]/68 transition data-[active=true]:border-[#009060] data-[active=true]:bg-[#009060] data-[active=true]:text-white dark:border-white/10 dark:text-[#f7f1df]/68 dark:data-[active=true]:border-[#e0a800] dark:data-[active=true]:bg-[#e0a800] dark:data-[active=true]:text-[#07120f]">
                            <x-hugeicon :name="$option['icon']" class="text-base" />
                            <span>{{ $option['label'] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </nav>
    </header>

    <div data-whatsapp-reveal-sentinel aria-hidden="true" class="pointer-events-none absolute start-0 top-40 size-px">
    </div>

    <main class="relative min-h-screen pb-24 pt-20 lg:pb-0">
        @yield('content')
    </main>

    <nav class="fixed inset-x-0 bottom-0 z-50 border-t border-[#009060]/12 bg-white/94 px-3 pb-[max(env(safe-area-inset-bottom),0.75rem)] pt-2 shadow-[0_-10px_30px_rgba(18,51,41,0.08)] backdrop-blur-xl lg:hidden dark:border-white/10 dark:bg-[#07120f]/94 dark:shadow-black/30"
        aria-label="التنقل السفلي">
        <div class="mx-auto grid max-w-md grid-cols-4 gap-1">
            @foreach ($bottomNavigationLinks as $link)
                @php
                    $isAccount = $link['label'] === 'حسابي';
                    $isActive = $isAccount
                        ? request()->routeIs('customer.*') || request()->routeIs('login', 'register', 'password.*')
                        : request()->routeIs($link['active']);
                @endphp

                @if (Route::has($link['route']))
                    <a href="{{ route($link['route']) }}"
                        class="relative flex min-h-14 flex-col items-center justify-center gap-1 rounded-sm text-xs font-semibold transition {{ $isActive ? 'text-[#009060] dark:text-[#e0a800]' : 'text-[#123329]/60 hover:bg-[#009060]/8 hover:text-[#009060] dark:text-[#f7f1df]/58 dark:hover:bg-white/10 dark:hover:text-[#e0a800]' }}">
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

    <footer data-public-footer
        class="border-t border-[#2a8069]/12 bg-white/68 pb-28 dark:border-white/10 dark:bg-white/5 lg:pb-0">
        <div class="mx-auto grid w-full max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-[1.2fr_.8fr_.8fr] lg:px-8">
            <div class="max-w-xl">
                <div class="flex items-center gap-3">
                    <span
                        class="flex size-12 items-center justify-center rounded-sm bg-white/76 p-1 ring-1 ring-[#2a8069]/12 dark:bg-white/10 dark:ring-white/10">
                        <img src="{{ asset('logo-dark.png') }}" alt="{{ $publicName }}"
                            class="h-10 w-auto object-contain dark:hidden">
                        <img src="{{ asset('logo.png') }}" alt="{{ $publicName }}"
                            class="hidden h-10 w-auto object-contain dark:block">
                    </span>
                    <div>
                        <p class="font-heading text-xl font-bold text-[#123329] dark:text-[#f7f1df]">{{ $publicName }}</p>
                        <p class="mt-1 text-sm text-[#123329]/58 dark:text-[#f7f1df]/62">تجارب سياحية وتعليمية بروح
                            عُمانية فاخرة.</p>
                    </div>
                </div>
            </div>

            <nav class="grid content-start gap-3 text-sm text-[#123329]/62 dark:text-[#f7f1df]/62"
                aria-label="روابط التذييل">
                <p class="font-heading text-base font-bold text-[#123329] dark:text-[#f7f1df]">الموقع</p>
                @foreach ($footerNavigationLinks as $link)
                    <a href="{{ route($link['route']) }}"
                        class="transition hover:text-[#009060] dark:hover:text-[#e0a800]">{{ $link['label'] }}</a>
                @endforeach
                @foreach ($publishedPolicyPages ?? [] as $policy)
                    <a href="{{ route('policies.show', ['page' => $policy->key]) }}"
                        class="transition hover:text-[#009060] dark:hover:text-[#e0a800]">{{ $policy->title }}</a>
                @endforeach
            </nav>

            <nav class="grid content-start gap-3 text-sm text-[#123329]/62 dark:text-[#f7f1df]/62"
                aria-label="روابط الحسابات">
                <p class="font-heading text-base font-bold text-[#123329] dark:text-[#f7f1df]">الحسابات</p>
                @if (Route::has('login'))
                    <a href="{{ route('login') }}"
                        class="transition hover:text-[#009060] dark:hover:text-[#e0a800]">{{ __('ui.auth.login_title') }}</a>
                @endif
                @if (Route::has('register'))
                    <a href="{{ route('register') }}"
                        class="transition hover:text-[#009060] dark:hover:text-[#e0a800]">{{ __('ui.auth.create_account') }}</a>
                @endif
                @if (Route::has('affiliate.login'))
                    <a href="{{ route('affiliate.login') }}"
                        class="transition hover:text-[#009060] dark:hover:text-[#e0a800]">{{ __('ui.affiliates.login_title') }}</a>
                @endif
                @if (Route::has('affiliate.register'))
                    <a href="{{ route('affiliate.register') }}"
                        class="transition hover:text-[#009060] dark:hover:text-[#e0a800]">{{ __('ui.affiliates.register_title') }}</a>
                @endif
            </nav>
        </div>

        <div class="border-t border-[#2a8069]/12 dark:border-white/10">
            <div class="mx-auto flex w-full max-w-7xl flex-col gap-2 px-4 py-5 text-center text-sm text-[#123329]/58 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:text-start lg:px-8 dark:text-[#f7f1df]/62">
                <p>© {{ now()->year }} {{ $publicName }}. جميع الحقوق محفوظة.</p>
                <p>
                    تطوير
                    <a href="https://red1ai.com/" target="_blank" rel="noopener noreferrer"
                        class="font-bold text-[#007a52] transition hover:text-[#005c3e] dark:text-[#e0a800] dark:hover:text-[#f0c96a]">ردء</a>
                </p>
            </div>
        </div>
    </footer>

    <livewire:store.floating-cart />

    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" data-whatsapp-floating-button
        class="pointer-events-none invisible fixed bottom-24 end-4 z-[60] inline-flex max-w-[calc(100vw-2rem)] translate-y-3 items-center gap-3 rounded-sm bg-[#25d366] px-4 py-3 text-white opacity-0 shadow-2xl shadow-[#123329]/24 ring-1 ring-white/28 transition duration-300 hover:-translate-y-0.5 hover:bg-[#1fbd59] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#dfb458] motion-reduce:transition-none lg:bottom-6 lg:end-6"
        title="تحتاج مساعدة؟ تحدث مع مساعدنا الذكي" aria-label="تحتاج مساعدة؟ تحدث مع مساعدنا الذكي">
        <span class="flex size-11 shrink-0 items-center justify-center rounded-sm bg-white/16">
            <svg class="size-6" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor">
                <path
                    d="M12.04 2a9.87 9.87 0 0 0-8.49 14.92L2.2 22l5.22-1.33A9.86 9.86 0 1 0 12.04 2Zm0 1.82a8.04 8.04 0 1 1-4.1 14.96l-.31-.18-3.03.77.79-2.95-.2-.32a8.04 8.04 0 0 1 6.85-12.28Zm-3.42 3.9c-.18 0-.46.07-.7.33-.24.27-.92.9-.92 2.2 0 1.29.94 2.54 1.07 2.72.13.18 1.82 2.91 4.49 3.96 2.22.87 2.67.7 3.15.66.48-.04 1.55-.63 1.77-1.24.22-.61.22-1.13.15-1.24-.06-.11-.24-.18-.5-.31-.26-.13-1.55-.77-1.79-.85-.24-.09-.41-.13-.59.13-.18.26-.68.85-.83 1.02-.15.18-.31.2-.57.07-.26-.13-1.1-.41-2.1-1.3-.78-.69-1.3-1.55-1.45-1.81-.15-.26-.02-.4.12-.53.12-.12.26-.31.39-.46.13-.15.18-.26.26-.44.09-.18.04-.33-.02-.46-.07-.13-.59-1.42-.81-1.95-.21-.51-.43-.44-.59-.45h-.5Z" />
            </svg>
        </span>
        <span class="grid min-w-0 text-start leading-tight">
            <span class="text-sm font-bold">تحتاج مساعدة؟</span>
            <span class="text-xs text-white/86">تحدث مع مساعدنا الذكي</span>
        </span>
    </a>

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    @fluxScripts
</body>

</html>
