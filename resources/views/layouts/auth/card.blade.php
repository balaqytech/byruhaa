<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#f7fbf8] antialiased dark:bg-[#071714]">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-md flex-col gap-6">
                <div class="flex justify-center">
                    <x-app-logo href="{{ route('home') }}" wire:navigate class="justify-center" />
                </div>

                <div class="flex flex-col gap-6">
                    <div class="rounded-xl border border-emerald-900/10 bg-white text-stone-800 shadow-sm dark:border-white/10 dark:bg-[#0b221e] dark:text-white">
                        <div class="px-10 py-8">{{ $slot }}</div>
                    </div>
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
