<x-layouts::app :title="__('ui.dashboard.heading')">
    <section class="flex flex-col gap-8">
        <div class="grid gap-6 lg:grid-cols-[1.25fr_.75fr] lg:items-stretch">
            <div class="overflow-hidden rounded-2xl bg-emerald-900 p-6 text-white shadow-sm md:p-8">
                <div class="flex max-w-2xl flex-col gap-5">
                    <span class="w-fit rounded-full border border-amber-300/30 bg-amber-300/15 px-3 py-1 text-sm font-medium text-amber-100">
                        {{ __('ui.brand') }}
                    </span>

                    <div class="space-y-3">
                        <flux:heading size="xl" class="text-white">{{ __('ui.dashboard.heading') }}</flux:heading>
                        <flux:text class="max-w-xl text-base text-emerald-50">
                            {{ __('ui.dashboard.subheading') }}
                        </flux:text>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <flux:button :href="route('events.index')" wire:navigate icon="calendar-days" class="border-0 bg-amber-300 text-emerald-950 hover:bg-amber-200">
                            {{ __('ui.actions.view_events') }}
                        </flux:button>
                        <a href="{{ route('family-members.index') }}" wire:navigate class="inline-flex min-h-10 items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium text-emerald-50 transition hover:bg-white/10 hover:text-white">
                            <flux:icon.users class="size-4" />
                            {{ __('ui.actions.manage_family') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                <a href="{{ route('events.index') }}" wire:navigate class="rounded-xl border border-emerald-900/10 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-700/30 hover:shadow-md dark:border-white/10 dark:bg-white/5">
                    <div class="flex items-center gap-3">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-200">
                            <flux:icon.calendar-days class="size-5" />
                        </span>
                        <div>
                            <flux:heading>{{ __('ui.labels.events') }}</flux:heading>
                            <flux:text>{{ __('ui.dashboard.events_description') }}</flux:text>
                        </div>
                    </div>
                </a>

                <a href="{{ route('family-members.index') }}" wire:navigate class="rounded-xl border border-emerald-900/10 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-700/30 hover:shadow-md dark:border-white/10 dark:bg-white/5">
                    <div class="flex items-center gap-3">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-300/15 dark:text-amber-200">
                            <flux:icon.users class="size-5" />
                        </span>
                        <div>
                            <flux:heading>{{ __('ui.labels.family') }}</flux:heading>
                            <flux:text>{{ __('ui.dashboard.family_description') }}</flux:text>
                        </div>
                    </div>
                </a>

                <a href="{{ route('bookings.index') }}" wire:navigate class="rounded-xl border border-emerald-900/10 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-700/30 hover:shadow-md dark:border-white/10 dark:bg-white/5">
                    <div class="flex items-center gap-3">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-sky-100 text-sky-800 dark:bg-sky-300/15 dark:text-sky-200">
                            <flux:icon.clipboard-document-list class="size-5" />
                        </span>
                        <div>
                            <flux:heading>{{ __('ui.labels.bookings') }}</flux:heading>
                            <flux:text>{{ __('ui.dashboard.bookings_description') }}</flux:text>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </section>
</x-layouts::app>
