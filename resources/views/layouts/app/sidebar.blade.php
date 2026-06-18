<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        @php($customer = auth('customer')->user())

        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('ui.labels.platform')" class="grid">
                    <flux:sidebar.item :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        <span class="inline-flex items-center gap-2">
                            <x-hugeicon name="home-01" class="text-lg" />
                            {{ __('ui.labels.dashboard') }}
                        </span>
                    </flux:sidebar.item>
                    <flux:sidebar.item :href="route('events.index')" :current="request()->routeIs('events.*')" wire:navigate>
                        <span class="inline-flex items-center gap-2">
                            <x-hugeicon name="calendar-03" class="text-lg" />
                            {{ __('ui.labels.events') }}
                        </span>
                    </flux:sidebar.item>
                    <flux:sidebar.item :href="route('family-members.index')" :current="request()->routeIs('family-members.*')" wire:navigate>
                        <span class="inline-flex items-center gap-2">
                            <x-hugeicon name="user-group" class="text-lg" />
                            {{ __('ui.labels.family') }}
                        </span>
                    </flux:sidebar.item>
                    <flux:sidebar.item :href="route('bookings.index')" :current="request()->routeIs('bookings.*')" wire:navigate>
                        <span class="inline-flex items-center gap-2">
                            <x-hugeicon name="contracts" class="text-lg" />
                            {{ __('ui.labels.bookings') }}
                        </span>
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="$customer->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" inset="left">
                <x-hugeicon name="menu-01" class="text-xl" />
            </flux:sidebar.toggle>

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="$customer->initials()"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="$customer->name"
                                    :initials="$customer->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ $customer->name }}</flux:heading>
                                    <flux:text class="truncate">{{ $customer->email ?? $customer->phone_number }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" wire:navigate>
                            <span class="inline-flex items-center gap-2">
                                <x-hugeicon name="account-setting-01" class="text-lg" />
                                {{ __('ui.labels.settings') }}
                            </span>
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

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
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
