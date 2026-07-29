<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    @include('partials.head')

    @isset($metaDescription)
        <meta name="description" content="{{ $metaDescription }}">
        <meta property="og:description" content="{{ $metaDescription }}">
    @endisset

    <meta property="og:title"
        content="{{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $eventUrl ?? url()->current() }}">
    <link rel="canonical" href="{{ $eventUrl ?? url()->current() }}">

    @isset($metaImage)
        <meta property="og:image" content="{{ $metaImage }}">
        <meta name="twitter:card" content="summary_large_image">
    @endisset

    @stack('head')
</head>

<body class="{{ $bodyClass ?? 'min-h-screen antialiased' }}">
    @yield('content')

    @stack('scripts')
</body>

</html>
