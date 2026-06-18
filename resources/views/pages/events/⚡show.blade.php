<?php

use App\Enums\EventStatus;
use App\Models\Booking;
use App\Models\Event;
use App\Models\FamilyMember;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Event details')] class extends Component {
    public Event $event;

    /** @var array<int> */
    public array $familyMemberIds = [];

    public function mount(Event $event): void
    {
        abort_unless($event->status === EventStatus::Published, 404);

        $this->event = $event;
    }

    public function book(): void
    {
        $customer = Auth::guard('customer')->user();

        $validated = $this->validate([
            'familyMemberIds' => ['required', 'array', 'min:1'],
            'familyMemberIds.*' => ['integer'],
        ]);

        $familyMembers = FamilyMember::query()
            ->whereBelongsTo($customer)
            ->whereIn('id', $validated['familyMemberIds'])
            ->get();

        if ($familyMembers->count() !== count(array_unique($validated['familyMemberIds']))) {
            throw ValidationException::withMessages([
                'familyMemberIds' => __('One or more selected family members are invalid.'),
            ]);
        }

        foreach ($familyMembers as $familyMember) {
            $age = $familyMember->ageAt($this->event->starts_at ?? now());

            if ($age < $this->event->minimum_age || $age > $this->event->maximum_age) {
                throw ValidationException::withMessages([
                    'familyMemberIds' => __('All selected family members must be within the event age range.'),
                ]);
            }
        }

        if ($familyMembers->count() > $this->event->remainingSeats()) {
            throw ValidationException::withMessages([
                'familyMemberIds' => __('This event does not have enough remaining seats.'),
            ]);
        }

        $booking = Booking::create([
            'customer_id' => $customer->id,
            'event_id' => $this->event->id,
        ]);

        foreach ($familyMembers as $familyMember) {
            $booking->familyMembers()->create([
                'family_member_id' => $familyMember->id,
            ]);
        }

        Flux::toast(variant: 'success', text: __('Booking request submitted.'));

        $this->redirectRoute('bookings.show', $booking, navigate: true);
    }

    public function with(): array
    {
        return [
            'familyMembers' => Auth::guard('customer')->user()
                ->familyMembers()
                ->oldest('birth_date')
                ->get(),
        ];
    }
}; ?>

<section class="mx-auto flex w-full max-w-6xl flex-col gap-6">
    <div class="flex flex-col gap-2">
        <flux:badge>{{ ucfirst($event->type) }}</flux:badge>
        <flux:heading size="xl">{{ $event->name }}</flux:heading>
        <flux:subheading>{{ $event->location }} · {{ $event->starts_at?->format('M j, Y H:i') ?? __('Date to be announced') }}</flux:subheading>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        <div class="space-y-6">
            <flux:card>
                <div class="prose max-w-none dark:prose-invert">
                    {!! $event->description_html !!}
                </div>
            </flux:card>
        </div>

        <flux:card>
            <form wire:submit="book" class="space-y-5">
                <div>
                    <flux:heading>{{ __('Book this event') }}</flux:heading>
                    <flux:text>{{ $event->remainingSeats() }} {{ __('approved seats remain') }}</flux:text>
                </div>

                <flux:checkbox.group wire:model="familyMemberIds" :label="__('Family members')">
                    @forelse ($familyMembers as $familyMember)
                        <flux:checkbox wire:key="event-family-member-{{ $familyMember->id }}" value="{{ $familyMember->id }}" :label="$familyMember->name.' · '.__('Age').' '.$familyMember->ageAt($event->starts_at ?? now())" />
                    @empty
                        <flux:text>{{ __('Add a family member before booking.') }}</flux:text>
                    @endforelse
                </flux:checkbox.group>

                <flux:error name="familyMemberIds" />

                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary" icon="clipboard-document-check" :disabled="$familyMembers->isEmpty()">
                        {{ __('Submit request') }}
                    </flux:button>
                    <flux:button :href="route('family-members.index')" wire:navigate>
                        {{ __('Manage family') }}
                    </flux:button>
                </div>
            </form>
        </flux:card>
    </div>
</section>
