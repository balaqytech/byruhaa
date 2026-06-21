<flux:dropdown position="bottom" align="start">
    @php($customer = auth('customer')->user())

    <flux:sidebar.profile
        :name="$customer->name"
        :initials="$customer->initials()"
        data-test="sidebar-menu-button"
    />

    <flux:menu>
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
        <flux:menu.separator />
        <flux:menu.radio.group>
            <flux:menu.item :href="route('customer.profile.edit')" wire:navigate>
                <span class="inline-flex items-center gap-2">
                    <x-hugeicon name="account-setting-01" class="text-lg" />
                    {{ __('ui.labels.settings') }}
                </span>
            </flux:menu.item>
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
        </flux:menu.radio.group>
    </flux:menu>
</flux:dropdown>
