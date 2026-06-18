<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Bookings')] class extends Component {
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

<section class="mx-auto flex w-full max-w-6xl flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ __('Bookings') }}</flux:heading>
        <flux:subheading>{{ __('Track event booking requests and contracts.') }}</flux:subheading>
    </div>

    <div class="space-y-3">
        @forelse ($bookings as $booking)
            <flux:card wire:key="booking-{{ $booking->id }}">
                <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                    <div>
                        <flux:heading>{{ $booking->event->name }}</flux:heading>
                        <flux:text>{{ $booking->reference }} · {{ $booking->familyMembers->count() }} {{ __('family member(s)') }}</flux:text>
                    </div>
                    <div class="flex items-center gap-3">
                        <flux:badge>{{ $booking->state->label() }}</flux:badge>
                        <flux:button :href="route('bookings.show', $booking)" wire:navigate icon="arrow-right">
                            {{ __('Open') }}
                        </flux:button>
                    </div>
                </div>
            </flux:card>
        @empty
            <flux:card>
                <flux:text>{{ __('You have not submitted any booking requests yet.') }}</flux:text>
            </flux:card>
        @endforelse
    </div>
</section>
