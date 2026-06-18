<x-layouts::auth :title="__('ui.actions.log_in')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('ui.auth.login_title')" :description="__('ui.auth.login_description')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />


        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-6">
            @csrf

            <flux:input
                name="login"
                :label="__('ui.auth.email_or_phone')"
                :value="old('login')"
                type="text"
                required
                autofocus
                autocomplete="username"
                placeholder="email@example.com or 9XXXXXXX"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('ui.fields.password')"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('ui.fields.password')"
                    viewable
                />

                @if (Route::has('password.request'))
                    <flux:link class="absolute top-0 text-sm end-0" :href="route('password.request')" wire:navigate>
                        {{ __('ui.auth.forgot_your_password') }}
                    </flux:link>
                @endif
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('ui.auth.remember_me')" :checked="old('remember')" />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('ui.actions.log_in') }}
                </flux:button>
            </div>
        </form>

        <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">
            <span>{{ __('ui.auth.dont_have_account') }}</span>
            <flux:link :href="route('register')" wire:navigate>{{ __('ui.actions.sign_up') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
