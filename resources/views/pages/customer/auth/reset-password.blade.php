@extends('layouts.public', ['title' => __('ui.auth.reset_password')])

@section('content')
    <section class="mx-auto flex min-h-[calc(100svh-5rem)] w-full max-w-md items-center px-4 py-16 sm:px-6 lg:px-8">
        <div class="public-card w-full rounded-sm border border-[#2a8069]/12 bg-white/78 p-6 shadow-xl shadow-[#123329]/8 dark:border-white/10 dark:bg-white/8 dark:shadow-black/20">
            <div class="flex flex-col gap-6">
        <x-auth-header :title="__('ui.auth.reset_password')" :description="__('ui.auth.reset_password_description')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-6">
            @csrf
            <!-- Token -->
            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <!-- Email Address -->
            <flux:input
                name="email"
                value="{{ request('email') }}"
                :label="__('ui.fields.email')"
                type="email"
                required
                autocomplete="email"
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
                <flux:button type="submit" variant="primary" class="w-full" data-test="reset-password-button">
                    {{ __('ui.auth.reset_password') }}
                </flux:button>
            </div>
        </form>
            </div>
        </div>
    </section>
@endsection
