<?php

use App\Actions\CalculateBookingPrice;
use App\Enums\EventStatus;
use App\Models\Booking;
use App\Models\Event;
use App\Models\FamilyMember;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('تفاصيل الفعالية')] class extends Component {
    public Event $event;

    /** @var array<int> */
    public array $familyMemberIds = [];

    public function mount(Event $event): void
    {
        abort_unless($event->status === EventStatus::Published, 404);

        $this->event = $event;
    }

    public function book(CalculateBookingPrice $calculateBookingPrice): void
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
                'familyMemberIds' => __('ui.messages.invalid_family_member_selection'),
            ]);
        }

        foreach ($familyMembers as $familyMember) {
            $age = $familyMember->ageAt($this->event->starts_at ?? now());

            if ($age < $this->event->minimum_age || $age > $this->event->maximum_age) {
                throw ValidationException::withMessages([
                    'familyMemberIds' => __('ui.messages.all_family_members_age_range'),
                ]);
            }
        }

        if ($familyMembers->count() > $this->event->remainingSeats()) {
            throw ValidationException::withMessages([
                'familyMemberIds' => __('ui.messages.not_enough_seats'),
            ]);
        }

        $booking = DB::transaction(function () use ($customer, $familyMembers, $calculateBookingPrice): Booking {
            $priceSnapshot = $calculateBookingPrice->execute($this->event, $familyMembers->count());

            $booking = Booking::create([
                'customer_id' => $customer->id,
                'event_id' => $this->event->id,
                ...$priceSnapshot->toBookingAttributes(),
            ]);

            foreach ($familyMembers as $familyMember) {
                $booking->familyMembers()->create([
                    'family_member_id' => $familyMember->id,
                ]);
            }

            return $booking;
        });

        Flux::toast(variant: 'success', text: __('ui.messages.booking_request_submitted'));

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

<section class="flex flex-col gap-6">
    <div class="rounded-2xl bg-emerald-900 p-6 text-white shadow-sm md:p-8">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div class="space-y-3">
                <span class="w-fit rounded-full border border-amber-300/30 bg-amber-300/15 px-3 py-1 text-sm font-medium text-amber-100">{{ $event->type->getLabel() }}</span>
                <flux:heading size="xl" class="text-white">{{ $event->name }}</flux:heading>
                <flux:text class="text-emerald-50">{{ $event->location }} · <span dir="ltr">{{ $event->starts_at?->format('Y-m-d H:i') ?? __('ui.events.date_to_be_announced') }}</span></flux:text>
            </div>
            <div class="rounded-xl bg-white/10 px-4 py-3">
                <flux:text class="text-emerald-50">{{ __('ui.events.approved_seats_remain') }}</flux:text>
                <div class="text-2xl font-semibold text-white">{{ $event->remainingSeats() }}</div>
            </div>
            <div class="rounded-xl bg-white/10 px-4 py-3">
                <flux:text class="text-emerald-50">{{ __('ui.events.price_per_family_member') }}</flux:text>
                <x-money :amount-baisa="$event->price_baisa" :currency="$event->currency" class="text-2xl font-semibold text-white" />
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        <div class="space-y-6">
            <div class="rounded-2xl border border-emerald-900/10 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/5">
                <div class="prose max-w-none dark:prose-invert">
                    {!! $event->description_html !!}
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
            <form wire:submit="book" class="space-y-5">
                <div>
                    <flux:heading>{{ __('ui.events.book_this_event') }}</flux:heading>
                    <flux:text>{{ $event->remainingSeats() }} {{ __('ui.events.approved_seats_remain') }}</flux:text>
                    <flux:text>{{ __('ui.events.price_per_family_member') }}: <x-money :amount-baisa="$event->price_baisa" :currency="$event->currency" /></flux:text>
                </div>

                <flux:checkbox.group wire:model="familyMemberIds" :label="__('ui.events.family_members')">
                    @forelse ($familyMembers as $familyMember)
                        <flux:checkbox wire:key="event-family-member-{{ $familyMember->id }}" value="{{ $familyMember->id }}" :label="$familyMember->name.' · '.__('ui.family.age').' '.$familyMember->ageAt($event->starts_at ?? now())" />
                    @empty
                        <flux:text>{{ __('ui.events.add_family_before_booking') }}</flux:text>
                    @endforelse
                </flux:checkbox.group>

                <flux:error name="familyMemberIds" />

                <div class="flex flex-wrap gap-3">
                    <flux:button type="submit" variant="primary" :disabled="$familyMembers->isEmpty()">
                        <x-hugeicon name="check-list" class="text-lg" />
                        {{ __('ui.actions.submit_request') }}
                    </flux:button>
                    <flux:button :href="route('family-members.index')" wire:navigate>
                        {{ __('ui.actions.manage_family') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</section>
