@extends('layouts.public', ['title' => __('ui.actions.sign_up')])

@section('content')
    <section class="mx-auto flex min-h-[calc(100svh-5rem)] w-full max-w-md items-center px-4 py-16 sm:px-6 lg:px-8">
        <div class="public-card w-full rounded-sm border border-[#2a8069]/12 bg-white/78 p-6 shadow-xl shadow-[#123329]/8 dark:border-white/10 dark:bg-white/8 dark:shadow-black/20">
            <div class="flex flex-col gap-6">
        <x-auth-header :title="__('ui.auth.create_account')" :description="__('ui.auth.create_account_description')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Name -->
            <flux:input
                name="name"
                :label="__('ui.fields.name')"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('ui.fields.full_name')"
            />

            <flux:input
                name="email"
                :label="__('ui.fields.email_address')"
                :value="old('email')"
                type="email"
                autocomplete="email"
                placeholder="email@example.com"
            />

            <flux:input
                name="phone_number"
                :label="__('ui.fields.phone_number')"
                :value="old('phone_number')"
                type="tel"
                autocomplete="tel"
                placeholder="9XXXXXXX"
            />

            <!-- Password -->
            <flux:input
                name="password"
                :label="__('ui.fields.password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('ui.fields.password')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                :label="__('ui.fields.password_confirmation')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('ui.fields.password_confirmation')"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex items-center justify-end">
                <flux:button type="submit" variant="primary" class="w-full" data-test="register-user-button">
                    {{ __('ui.actions.create_account') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('ui.auth.already_have_account') }}</span>
            <flux:link :href="route('login')" wire:navigate>{{ __('ui.actions.log_in') }}</flux:link>
        </div>
            </div>
        </div>
    </section>
@endsection
