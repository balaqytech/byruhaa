<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
    <head>
        @include('partials.head')

        @isset($metaDescription)
            <meta name="description" content="{{ $metaDescription }}">
            <meta property="og:description" content="{{ $metaDescription }}">
        @endisset

        <meta property="og:title" content="{{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">

        @isset($metaImage)
            <meta property="og:image" content="{{ $metaImage }}">
            <meta name="twitter:card" content="summary_large_image">
        @endisset
    </head>
    <body class="min-h-screen bg-[#f7fbf8] text-[#15372f] antialiased dark:bg-[#071714] dark:text-[#edf7f2]">
        <header class="sticky top-0 z-40 border-b border-emerald-900/10 bg-white/90 shadow-sm shadow-emerald-950/5 backdrop-blur dark:border-white/10 dark:bg-[#09221d]/90">
            <div class="mx-auto flex min-h-16 w-full max-w-7xl items-center gap-3 px-4 sm:px-6 lg:px-8">
                <x-app-logo href="{{ route('home') }}" />

                <nav class="ms-6 flex items-center gap-2 text-sm font-medium">
                    <a href="{{ route('blog.index') }}" class="rounded-full px-3 py-2 text-emerald-950/80 transition hover:bg-emerald-50 hover:text-emerald-900 dark:text-emerald-50/80 dark:hover:bg-white/10 dark:hover:text-white">
                        {{ __('ui.labels.blog') }}
                    </a>
                    @auth('customer')
                        <a href="{{ route('dashboard') }}" class="rounded-full px-3 py-2 text-emerald-950/80 transition hover:bg-emerald-50 hover:text-emerald-900 dark:text-emerald-50/80 dark:hover:bg-white/10 dark:hover:text-white">
                            {{ __('ui.labels.dashboard') }}
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-full px-3 py-2 text-emerald-950/80 transition hover:bg-emerald-50 hover:text-emerald-900 dark:text-emerald-50/80 dark:hover:bg-white/10 dark:hover:text-white">
                            {{ __('ui.actions.log_in') }}
                        </a>
                    @endauth
                </nav>
            </div>
        </header>

        <main class="relative mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            @yield('content')
        </main>
    </body>
</html>
