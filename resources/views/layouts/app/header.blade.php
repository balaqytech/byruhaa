<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#f7fbf8] text-[#15372f] antialiased dark:bg-[#071714] dark:text-[#edf7f2]">
        @php($customer = auth('customer')->user())

        <flux:header container class="sticky top-0 z-40 border-b border-emerald-900/10 bg-white/90 shadow-sm shadow-emerald-950/5 backdrop-blur dark:border-white/10 dark:bg-[#09221d]/90">
            <div class="flex min-h-16 w-full items-center gap-3">
                <x-app-logo href="{{ route('customer.dashboard') }}" wire:navigate />

                <flux:navbar class="ms-8 hidden gap-1 lg:flex">
                    <flux:navbar.item :href="route('customer.dashboard')" :current="request()->routeIs('customer.dashboard')" wire:navigate>
                        <span class="inline-flex items-center gap-2">
                            <x-hugeicon name="home-01" class="text-lg" />
                            {{ __('ui.labels.dashboard') }}
                        </span>
                    </flux:navbar.item>
                    <flux:navbar.item :href="route('customer.events.index')" :current="request()->routeIs('customer.events.*')" wire:navigate>
                        <span class="inline-flex items-center gap-2">
                            <x-hugeicon name="calendar-03" class="text-lg" />
                            {{ __('ui.labels.events') }}
                        </span>
                    </flux:navbar.item>
                    <flux:navbar.item :href="route('customer.family-members.index')" :current="request()->routeIs('customer.family-members.*')" wire:navigate>
                        <span class="inline-flex items-center gap-2">
                            <x-hugeicon name="user-group" class="text-lg" />
                            {{ __('ui.labels.family') }}
                        </span>
                    </flux:navbar.item>
                    <flux:navbar.item :href="route('customer.bookings.index')" :current="request()->routeIs('customer.bookings.*')" wire:navigate>
                        <span class="inline-flex items-center gap-2">
                            <x-hugeicon name="contracts" class="text-lg" />
                            {{ __('ui.labels.bookings') }}
                        </span>
                    </flux:navbar.item>
                    <flux:navbar.item :href="route('customer.payments.index')" :current="request()->routeIs('customer.payments.index')" wire:navigate>
                        <span class="inline-flex items-center gap-2">
                            <x-hugeicon name="wallet-02" class="text-lg" />
                            {{ __('ui.labels.payments') }}
                        </span>
                    </flux:navbar.item>
                </flux:navbar>

                <flux:spacer />

                <flux:dropdown position="bottom" align="end" class="lg:hidden">
                    <flux:button variant="ghost" aria-label="{{ __('ui.labels.dashboard') }}">
                        <x-hugeicon name="menu-01" class="text-xl" />
                    </flux:button>

                    <flux:menu>
                        <flux:menu.item :href="route('customer.dashboard')" wire:navigate>
                            <span class="inline-flex items-center gap-2">
                                <x-hugeicon name="home-01" class="text-lg" />
                                {{ __('ui.labels.dashboard') }}
                            </span>
                        </flux:menu.item>
                        <flux:menu.item :href="route('customer.events.index')" wire:navigate>
                            <span class="inline-flex items-center gap-2">
                                <x-hugeicon name="calendar-03" class="text-lg" />
                                {{ __('ui.labels.events') }}
                            </span>
                        </flux:menu.item>
                        <flux:menu.item :href="route('customer.family-members.index')" wire:navigate>
                            <span class="inline-flex items-center gap-2">
                                <x-hugeicon name="user-group" class="text-lg" />
                                {{ __('ui.labels.family') }}
                            </span>
                        </flux:menu.item>
                        <flux:menu.item :href="route('customer.bookings.index')" wire:navigate>
                            <span class="inline-flex items-center gap-2">
                                <x-hugeicon name="contracts" class="text-lg" />
                                {{ __('ui.labels.bookings') }}
                            </span>
                        </flux:menu.item>
                        <flux:menu.item :href="route('customer.payments.index')" wire:navigate>
                            <span class="inline-flex items-center gap-2">
                                <x-hugeicon name="wallet-02" class="text-lg" />
                                {{ __('ui.labels.payments') }}
                            </span>
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>

                <flux:dropdown position="bottom" align="end">
                    <flux:profile
                        :name="$customer->name"
                        :initials="$customer->initials()"
                    />

                    <flux:menu>
                        <div class="px-2 py-1.5 text-sm">
                            <flux:heading class="truncate">{{ $customer->name }}</flux:heading>
                            <flux:text class="truncate">{{ $customer->email ?? $customer->phone_number }}</flux:text>
                        </div>

                        <flux:menu.separator />

                        <flux:menu.item :href="route('customer.profile.edit')" wire:navigate>
                            <span class="inline-flex items-center gap-2">
                                <x-hugeicon name="account-setting-01" class="text-lg" />
                                {{ __('ui.labels.settings') }}
                            </span>
                        </flux:menu.item>

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item
                                as="button"
                                type="submit"
                                class="w-full cursor-pointer"
                                data-test="logout-button"
                            >
                                <span class="inline-flex items-center gap-2">
                                    <x-hugeicon name="logout-01" class="text-lg" />
                                    {{ __('ui.actions.log_out') }}
                                </span>
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </flux:header>

        <main class="relative mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-56 bg-[radial-gradient(circle_at_70%_0%,rgba(34,197,94,0.18),transparent_34%),linear-gradient(180deg,rgba(245,158,11,0.10),transparent)] dark:bg-[radial-gradient(circle_at_70%_0%,rgba(16,185,129,0.18),transparent_34%)]"></div>

            {{ $slot }}
        </main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
