<x-layouts::app :title="__('Dashboard')">
    <section class="mx-auto flex w-full max-w-6xl flex-col gap-6">
        <div>
            <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>
            <flux:subheading>{{ __('Manage family profiles, event bookings, and contracts.') }}</flux:subheading>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <flux:card>
                <flux:heading>{{ __('Events') }}</flux:heading>
                <flux:text>{{ __('Browse published Byruhaa events.') }}</flux:text>
                <flux:button class="mt-4" :href="route('events.index')" wire:navigate icon="calendar-days">
                    {{ __('View events') }}
                </flux:button>
            </flux:card>

            <flux:card>
                <flux:heading>{{ __('Family') }}</flux:heading>
                <flux:text>{{ __('Keep reusable family member profiles ready for bookings.') }}</flux:text>
                <flux:button class="mt-4" :href="route('family-members.index')" wire:navigate icon="users">
                    {{ __('Manage family') }}
                </flux:button>
            </flux:card>

            <flux:card>
                <flux:heading>{{ __('Bookings') }}</flux:heading>
                <flux:text>{{ __('Track approvals, signatures, and downloadable contracts.') }}</flux:text>
                <flux:button class="mt-4" :href="route('bookings.index')" wire:navigate icon="clipboard-document-list">
                    {{ __('View bookings') }}
                </flux:button>
            </flux:card>
        </div>
    </section>
</x-layouts::app>
