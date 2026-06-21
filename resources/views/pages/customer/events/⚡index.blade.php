<?php

use App\Enums\EventStatus;
use App\Models\Event;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('الفعاليات')] class extends Component {
    public function with(): array
    {
        return [
            'events' => Event::query()
                ->where('status', EventStatus::Published)
                ->orderBy('starts_at')
                ->get(),
        ];
    }
}; ?>

<section class="flex flex-col gap-6">
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
            <flux:heading size="xl">{{ __('ui.events.heading') }}</flux:heading>
            <flux:subheading>{{ __('ui.events.subheading') }}</flux:subheading>
        </div>

        <flux:button :href="route('customer.family-members.index')" wire:navigate variant="outline">
            <x-hugeicon name="user-group" class="text-lg" />
            {{ __('ui.actions.manage_family') }}
        </flux:button>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($events as $event)
            <article wire:key="event-{{ $event->id }}" class="flex min-h-64 flex-col rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-700/30 hover:shadow-md dark:border-white/10 dark:bg-white/5">
                <div class="flex h-full flex-col gap-5">
                    <div>
                        <div class="flex items-start justify-between gap-3">
                            <flux:badge class="bg-emerald-50 text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-100">{{ $event->type->getLabel() }}</flux:badge>
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800 dark:bg-amber-300/15 dark:text-amber-100">
                                {{ $event->remainingSeats() }} {{ __('ui.events.seats_left') }}
                            </span>
                        </div>
                        <flux:heading class="mt-4">{{ $event->name }}</flux:heading>
                        <flux:text class="mt-2">{{ $event->excerpt }}</flux:text>
                    </div>

                    <div class="mt-auto grid gap-2 text-sm text-emerald-950/70 dark:text-emerald-50/70">
                        @if ($event->location)
                            <div class="flex items-center gap-2">
                                <x-hugeicon name="map-pin" class="text-base" />
                                <span>{{ $event->location }}</span>
                            </div>
                        @endif
                        @if ($event->starts_at)
                            <div class="flex items-center gap-2">
                                <x-hugeicon name="clock-01" class="text-base" />
                                <span dir="ltr">{{ $event->starts_at->format('Y-m-d H:i') }}</span>
                            </div>
                        @endif
                        <div class="flex items-center gap-2">
                            <x-hugeicon name="wallet-02" class="text-base" />
                            <span>{{ __('ui.events.price_per_family_member') }}:</span>
                            <x-money :amount-baisa="$event->price_baisa" :currency="$event->currency" />
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <flux:button :href="route('customer.events.show', $event)" wire:navigate>
                            <x-hugeicon name="arrow-left-02" class="text-lg" />
                            {{ __('ui.actions.view') }}
                        </flux:button>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-emerald-900/20 bg-white p-8 text-center dark:border-white/15 dark:bg-white/5 md:col-span-2 xl:col-span-3">
                <flux:text>{{ __('ui.events.empty') }}</flux:text>
            </div>
        @endforelse
    </div>
</section>
