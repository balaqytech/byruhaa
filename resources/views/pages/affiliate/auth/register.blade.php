<x-layouts::auth :title="__('ui.affiliates.register_title')">
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('ui.affiliates.register_title')" :description="__('ui.affiliates.register_description')" />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('affiliate.register.store') }}" class="flex flex-col gap-6">
            @csrf

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
                required
                autocomplete="tel"
                placeholder="9XXXXXXX"
            />

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

            <flux:button type="submit" variant="primary" class="w-full" data-test="affiliate-register-button">
                {{ __('ui.actions.create_account') }}
            </flux:button>
        </form>

        <div class="space-x-1 text-center text-sm text-zinc-600 rtl:space-x-reverse dark:text-zinc-400">
            <span>{{ __('ui.auth.already_have_account') }}</span>
            <flux:link :href="route('affiliate.login')" wire:navigate>{{ __('ui.actions.log_in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>
