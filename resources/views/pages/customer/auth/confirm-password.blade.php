@extends('layouts.public', ['title' => __('ui.auth.confirm_password')])

@section('content')
    <section class="mx-auto flex min-h-[calc(100svh-5rem)] w-full max-w-md items-center px-4 py-16 sm:px-6 lg:px-8">
        <div class="public-card w-full rounded-sm border border-[#2a8069]/12 bg-white/78 p-6 shadow-xl shadow-[#123329]/8 dark:border-white/10 dark:bg-white/8 dark:shadow-black/20">
            <div class="flex flex-col gap-6">
        <x-auth-header
            :title="__('ui.auth.confirm_password')"
            :description="__('ui.auth.confirm_password_description')"
        />

        <x-auth-session-status class="text-center" :status="session('status')" />


        <form method="POST" action="{{ route('password.confirm.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="password"
                :label="__('ui.fields.password')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('ui.fields.password')"
                viewable
            />

            <flux:button variant="primary" type="submit" class="w-full" data-test="confirm-password-button">
                {{ __('ui.actions.confirm') }}
            </flux:button>
        </form>
            </div>
        </div>
    </section>
@endsection
