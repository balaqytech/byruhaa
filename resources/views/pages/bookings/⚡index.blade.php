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

        <flux:button :href="route('events.index')" wire:navigate variant="outline">
            <x-hugeicon name="calendar-03" class="text-lg" />
            {{ __('ui.actions.view_events') }}
        </flux:button>
    </div>

    <div class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
        @if ($bookings->isNotEmpty())
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('admin.fields.reference') }}</flux:table.column>
                    <flux:table.column>{{ __('ui.labels.event') }}</flux:table.column>
                    <flux:table.column>{{ __('ui.events.family_members') }}</flux:table.column>
                    <flux:table.column>{{ __('ui.labels.status') }}</flux:table.column>
                    <flux:table.column>{{ __('ui.bookings.submitted') }}</flux:table.column>
                    <flux:table.column align="end">{{ __('ui.actions.open') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @foreach ($bookings as $booking)
                        <flux:table.row wire:key="booking-row-{{ $booking->id }}">
                            <flux:table.cell variant="strong">{{ $booking->reference }}</flux:table.cell>
                            <flux:table.cell>{{ $booking->event->name }}</flux:table.cell>
                            <flux:table.cell>{{ trans_choice('ui.bookings.family_member_count', $booking->familyMembers->count(), ['count' => $booking->familyMembers->count()]) }}</flux:table.cell>
                            <flux:table.cell>
                                <flux:badge>{{ $booking->state->label() }}</flux:badge>
                            </flux:table.cell>
                            <flux:table.cell dir="ltr">{{ $booking->created_at->format('Y-m-d') }}</flux:table.cell>
                            <flux:table.cell align="end">
                                <flux:button :href="route('bookings.show', $booking)" wire:navigate size="sm">
                                    <x-hugeicon name="arrow-left-02" class="text-base" />
                                    {{ __('ui.actions.open') }}
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>
        @else
            <div class="rounded-xl border border-dashed border-emerald-900/20 p-8 text-center dark:border-white/15">
                <flux:text>{{ __('ui.bookings.empty') }}</flux:text>
            </div>
        @endif
    </div>
</section>
