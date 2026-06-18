<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('الحجوزات')] class extends Component {
    public function with(): array
    {
        return [
            'bookings' => Auth::guard('customer')->user()
                ->bookings()
                ->with(['event', 'familyMembers.familyMember', 'familyMembers.contract'])
                ->latest()
                ->get(),
        ];
    }
}; ?>

<section class="flex flex-col gap-6">
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
            <flux:heading size="xl">{{ __('ui.bookings.heading') }}</flux:heading>
            <flux:subheading>{{ __('ui.bookings.subheading') }}</flux:subheading>
        </div>

        <flux:button :href="route('events.index')" wire:navigate icon="calendar-days" variant="outline">
            {{ __('ui.actions.view_events') }}
        </flux:button>
    </div>

    <div class="space-y-3">
        @forelse ($bookings as $booking)
            <div wire:key="booking-{{ $booking->id }}" class="rounded-2xl border border-emerald-900/10 bg-white p-4 shadow-sm transition hover:border-emerald-700/30 hover:shadow-md dark:border-white/10 dark:bg-white/5">
                <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <flux:heading>{{ $booking->event->name }}</flux:heading>
                        <flux:text>{{ $booking->reference }} · {{ trans_choice('ui.bookings.family_member_count', $booking->familyMembers->count(), ['count' => $booking->familyMembers->count()]) }}</flux:text>
                    </div>
                    <div class="flex items-center gap-3">
                        <flux:badge>{{ $booking->state->label() }}</flux:badge>
                        <flux:button :href="route('bookings.show', $booking)" wire:navigate icon="arrow-left">
                            {{ __('ui.actions.open') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-emerald-900/20 bg-white p-8 text-center dark:border-white/15 dark:bg-white/5">
                <flux:text>{{ __('ui.bookings.empty') }}</flux:text>
            </div>
        @endforelse
    </div>
</section>
