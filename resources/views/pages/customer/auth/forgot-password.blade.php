@extends('layouts.public', ['title' => __('ui.auth.forgot_password')])

@section('content')
    <section class="mx-auto flex min-h-[calc(100svh-5rem)] w-full max-w-md items-center px-4 py-16 sm:px-6 lg:px-8">
        <div class="public-card w-full rounded-sm border border-[#2a8069]/12 bg-white/78 p-6 shadow-xl shadow-[#123329]/8 dark:border-white/10 dark:bg-white/8 dark:shadow-black/20">
            <div class="flex flex-col gap-6">
        <x-auth-header :title="__('ui.auth.forgot_password')" :description="__('ui.auth.forgot_password_description')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('ui.fields.email_address')"
                type="email"
                required
                autofocus
                placeholder="email@example.com"
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="email-password-reset-link-button">
                {{ __('ui.actions.email_password_reset_link') }}
            </flux:button>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
            <span>{{ __('ui.auth.or_return_to') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('ui.actions.back_to_login') }}</flux:link>
        </div>
            </div>
        </div>
    </section>
@endsection
