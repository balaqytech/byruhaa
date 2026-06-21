<x-layouts::auth :title="__('ui.affiliates.login_title')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('ui.affiliates.login_title')" :description="__('ui.affiliates.login_description')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('affiliate.login.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="login"
                :label="__('ui.affiliates.email_or_phone')"
                :value="old('login')"
                type="text"
                required
                autofocus
                autocomplete="username"
                placeholder="email@example.com or 9XXXXXXX"
            />

            <flux:input
                name="password"
                :label="__('ui.fields.password')"
                type="password"
                required
                autocomplete="current-password"
                :placeholder="__('ui.fields.password')"
                viewable
            />

            <flux:checkbox name="remember" :label="__('ui.auth.remember_me')" :checked="old('remember')" />

            <flux:button variant="primary" type="submit" class="w-full" data-test="affiliate-login-button">
                {{ __('ui.actions.log_in') }}
            </flux:button>
        </form>

        <div class="space-x-1 text-center text-sm text-zinc-600 rtl:space-x-reverse dark:text-zinc-400">
            <span>{{ __('ui.auth.dont_have_account') }}</span>
            <flux:link :href="route('affiliate.register')" wire:navigate>{{ __('ui.actions.sign_up') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
