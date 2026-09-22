<nav {{ $attributes->class('flex gap-1 overflow-x-auto border-b border-emerald-900/10 dark:border-white/10') }} aria-label="حساب القاصر">
    @foreach ([
        ['label' => 'نظرة عامة', 'route' => 'minor.dashboard', 'active' => 'minor.dashboard', 'icon' => 'home-01'],
        ['label' => 'طلباتي', 'route' => 'minor.orders.index', 'active' => 'minor.orders.*', 'icon' => 'invoice-03'],
        ...(config('byruhaa.wallets.enabled', false) ? [['label' => 'المحفظة', 'route' => 'minor.wallet.movements.index', 'active' => 'minor.wallet.*', 'icon' => 'wallet-02']] : []),
        ['label' => 'الإشعارات', 'route' => 'minor.notifications.index', 'active' => 'minor.notifications.*', 'icon' => 'notification-02'],
    ] as $item)
        <a href="{{ route($item['route']) }}" class="inline-flex min-h-12 shrink-0 items-center gap-2 border-b-2 px-3 text-sm font-semibold transition {{ request()->routeIs($item['active']) ? 'border-emerald-700 text-emerald-800 dark:border-emerald-300 dark:text-emerald-200' : 'border-transparent text-emerald-900/55 hover:border-emerald-700/30 hover:text-emerald-800 dark:text-white/55 dark:hover:text-white' }}">
            <x-hugeicon :name="$item['icon']" class="text-lg" />
            {{ $item['label'] }}
        </a>
    @endforeach
</nav>
