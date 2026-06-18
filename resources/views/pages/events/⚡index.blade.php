<?php

use App\Enums\EventStatus;
use App\Models\Event;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Events')] class extends Component {
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

<section class="mx-auto flex w-full max-w-6xl flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Events') }}</flux:heading>
        <flux:subheading>{{ __('Browse published trips, camps, and festivals.') }}</flux:subheading>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($events as $event)
            <flux:card wire:key="event-{{ $event->id }}">
                <div class="flex h-full flex-col gap-4">
                    <div>
                        <flux:badge>{{ ucfirst($event->type) }}</flux:badge>
                        <flux:heading class="mt-3">{{ $event->name }}</flux:heading>
                        <flux:text>{{ $event->excerpt }}</flux:text>
                    </div>
                    <div class="mt-auto flex items-center justify-between gap-3">
                        <flux:text>{{ $event->remainingSeats() }} {{ __('seats left') }}</flux:text>
                        <flux:button :href="route('events.show', $event)" wire:navigate icon="arrow-right">
                            {{ __('View') }}
                        </flux:button>
                    </div>
                </div>
            </flux:card>
        @empty
            <flux:card class="md:col-span-2 xl:col-span-3">
                <flux:text>{{ __('No published events are available right now.') }}</flux:text>
            </flux:card>
        @endforelse
    </div>
</section>
