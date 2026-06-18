<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#f7fbf8] text-[#15372f] antialiased dark:bg-[#071714] dark:text-[#edf7f2]">
        @php($customer = auth('customer')->user())

        <flux:header container class="sticky top-0 z-40 border-b border-emerald-900/10 bg-white/90 shadow-sm shadow-emerald-950/5 backdrop-blur dark:border-white/10 dark:bg-[#09221d]/90">
            <div class="flex min-h-16 w-full items-center gap-3">
                <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

                <flux:navbar class="ms-8 hidden gap-1 lg:flex">
                    <flux:navbar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('ui.labels.dashboard') }}
                    </flux:navbar.item>
                    <flux:navbar.item icon="calendar-days" :href="route('events.index')" :current="request()->routeIs('events.*')" wire:navigate>
                        {{ __('ui.labels.events') }}
                    </flux:navbar.item>
                    <flux:navbar.item icon="users" :href="route('family-members.index')" :current="request()->routeIs('family-members.*')" wire:navigate>
                        {{ __('ui.labels.family') }}
                    </flux:navbar.item>
                    <flux:navbar.item icon="clipboard-document-list" :href="route('bookings.index')" :current="request()->routeIs('bookings.*')" wire:navigate>
                        {{ __('ui.labels.bookings') }}
                    </flux:navbar.item>
                </flux:navbar>

                <flux:spacer />

                <flux:dropdown position="bottom" align="end" class="lg:hidden">
                    <flux:button variant="ghost" icon="bars-3" />

                    <flux:menu>
                        <flux:menu.item icon="home" :href="route('dashboard')" wire:navigate>
                            {{ __('ui.labels.dashboard') }}
                        </flux:menu.item>
                        <flux:menu.item icon="calendar-days" :href="route('events.index')" wire:navigate>
                            {{ __('ui.labels.events') }}
                        </flux:menu.item>
                        <flux:menu.item icon="users" :href="route('family-members.index')" wire:navigate>
                            {{ __('ui.labels.family') }}
                        </flux:menu.item>
                        <flux:menu.item icon="clipboard-document-list" :href="route('bookings.index')" wire:navigate>
                            {{ __('ui.labels.bookings') }}
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>

                <flux:dropdown position="bottom" align="end">
                    <flux:profile
                        :name="$customer->name"
                        :initials="$customer->initials()"
                        icon-trailing="chevron-down"
                    />

                    <flux:menu>
                        <div class="px-2 py-1.5 text-sm">
                            <flux:heading class="truncate">{{ $customer->name }}</flux:heading>
                            <flux:text class="truncate">{{ $customer->email ?? $customer->phone_number }}</flux:text>
                        </div>

                        <flux:menu.separator />

                        <flux:menu.item :href="route('profile.edit')" icon="cog-6-tooth" wire:navigate>
                            {{ __('ui.labels.settings') }}
                        </flux:menu.item>

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item
                                as="button"
                                type="submit"
                                icon="arrow-right-start-on-rectangle"
                                class="w-full cursor-pointer"
                                data-test="logout-button"
                            >
                                {{ __('ui.actions.log_out') }}
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
