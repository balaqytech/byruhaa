<x-layouts::affiliate :title="__('ui.affiliates.pending_title')">
    <section class="mx-auto flex max-w-2xl flex-col gap-6">
        <div class="rounded-2xl bg-emerald-900 p-6 text-white shadow-sm md:p-8">
            <div class="flex items-start gap-4">
                <span class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-amber-300/15 text-amber-100 ring-1 ring-amber-200/20">
                    <x-hugeicon name="clock-01" class="text-3xl" />
                </span>
                <div class="space-y-2">
                    <flux:heading size="xl" class="text-white">{{ __('ui.affiliates.pending_title') }}</flux:heading>
                    <flux:text class="text-emerald-50">{{ __('ui.affiliates.pending_description') }}</flux:text>
                </div>
            </div>
        </div>
    </section>
</x-layouts::affiliate>
