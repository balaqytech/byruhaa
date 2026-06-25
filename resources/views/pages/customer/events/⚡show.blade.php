<?php

use App\Actions\CreateCustomerBooking;
use App\Enums\EventStatus;
use App\Models\Discount;
use App\Models\Event;
use App\Services\AffiliateAttribution;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
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

    public function book(CreateCustomerBooking $createCustomerBooking, AffiliateAttribution $affiliateAttribution): void
    {
        $customer = Auth::guard('customer')->user();
        $customer->ensureProfileIsComplete('familyMemberIds');

        $validated = $this->validate([
            'familyMemberIds' => ['required', 'array', 'min:1'],
            'familyMemberIds.*' => ['integer', 'distinct'],
        ]);

        $booking = $createCustomerBooking->execute(
            $customer,
            [
                'event_id' => $this->event->id,
                'family_member_ids' => $validated['familyMemberIds'],
            ],
            $affiliateAttribution->current(),
        );

        Flux::toast(variant: 'success', text: __('ui.messages.booking_request_submitted'));

        $this->redirectRoute('customer.bookings.show', $booking, navigate: true);
    }

    public function with(): array
    {
        return [
            'availableDiscounts' => $this->availableDiscounts(),
            'familyMembers' => Auth::guard('customer')->user()->familyMembers()->oldest('birth_date')->get(),
        ];
    }

    /**
     * @return EloquentCollection<int, Discount>
     */
    private function availableDiscounts(): EloquentCollection
    {
        return Discount::query()->availableForEvent($this->event)->orderByDesc('amount_baisa')->orderBy('id')->get();
    }
}; ?>

<section class="flex flex-col gap-6">
    <div class="rounded-2xl bg-emerald-900 p-6 text-white shadow-sm md:p-8">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div class="space-y-3">
                <span
                    class="w-fit rounded-full border border-amber-300/30 bg-amber-300/15 px-3 py-1 text-sm font-medium text-amber-100">{{ $event->type->getLabel() }}</span>
                <flux:heading size="xl" class="text-white">{{ $event->name }}</flux:heading>
                <flux:text class="text-emerald-50">{{ $event->location }} · <span
                        dir="ltr">{{ $event->starts_at?->format('Y-m-d H:i') ?? __('ui.events.date_to_be_announced') }}</span>
                </flux:text>
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
            <div
                class="rounded-2xl border border-emerald-900/10 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/5">
                <div
                    class="prose prose-zinc max-w-none dark:prose-invert prose-img:rounded-lg prose-a:text-emerald-700 dark:prose-a:text-emerald-300">
                    {!! $event->description_html !!}
                </div>
            </div>
        </div>

        <div class="space-y-6">

            @if ($availableDiscounts->isNotEmpty())
                <div
                    class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
                    <div class="mb-5 flex items-center justify-between gap-3">
                        <div>
                            <flux:heading>{{ __('ui.events.available_discounts') }}</flux:heading>
                            <flux:text>{{ __('ui.events.available_discounts_subheading') }}</flux:text>
                        </div>
                        <span
                            class="flex size-11 items-center justify-center rounded-xl bg-amber-100 text-amber-800 dark:bg-amber-300/15 dark:text-amber-100">
                            <x-hugeicon name="coupon-percent" class="text-2xl" />
                        </span>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-emerald-900/10 dark:border-white/10">
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>{{ __('ui.fields.name') }}</flux:table.column>
                                <flux:table.column>{{ __('ui.events.discount_per_family_member') }}</flux:table.column>
                                <flux:table.column>{{ __('ui.events.eligibility') }}</flux:table.column>
                                <flux:table.column>{{ __('ui.events.validity') }}</flux:table.column>
                            </flux:table.columns>

                            <flux:table.rows>
                                @foreach ($availableDiscounts as $discount)
                                    <flux:table.row wire:key="event-discount-{{ $discount->id }}">
                                        <flux:table.cell variant="strong">{{ $discount->name }}</flux:table.cell>
                                        <flux:table.cell><x-money :amount-baisa="$discount->amount_baisa" :currency="$discount->currency" />
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            @if ($discount->minimum_family_members && $discount->maximum_family_members && $discount->minimum_family_members === $discount->maximum_family_members)
                                                {{ __('ui.events.exact_family_members', ['count' => $discount->minimum_family_members]) }}
                                            @elseif ($discount->minimum_family_members && $discount->maximum_family_members)
                                                {{ __('ui.events.family_member_range', ['min' => $discount->minimum_family_members, 'max' => $discount->maximum_family_members]) }}
                                            @elseif ($discount->minimum_family_members)
                                                {{ __('ui.events.minimum_family_members', ['count' => $discount->minimum_family_members]) }}
                                            @elseif ($discount->maximum_family_members)
                                                {{ __('ui.events.maximum_family_members', ['count' => $discount->maximum_family_members]) }}
                                            @else
                                                {{ __('ui.events.all_family_sizes') }}
                                            @endif
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            @if ($discount->starts_at || $discount->ends_at)
                                                <span dir="ltr">
                                                    {{ $discount->starts_at?->format('Y-m-d') ?? __('ui.events.always_available') }}
                                                    -
                                                    {{ $discount->ends_at?->format('Y-m-d') ?? __('ui.events.open_ended') }}
                                                </span>
                                            @else
                                                {{ __('ui.events.always_available') }}
                                            @endif
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </div>
                </div>
            @endif
            <form wire:submit="book"
                class="space-y-5 rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
                <div>
                    <flux:heading>{{ __('ui.events.book_this_event') }}</flux:heading>
                    <flux:text>{{ $event->remainingSeats() }} {{ __('ui.events.approved_seats_remain') }}</flux:text>
                    <flux:text>{{ __('ui.events.price_per_family_member') }}: <x-money :amount-baisa="$event->price_baisa"
                            :currency="$event->currency" /></flux:text>
                </div>

                <flux:checkbox.group wire:model="familyMemberIds" :label="__('ui.events.family_members')">
                    @forelse ($familyMembers as $familyMember)
                        <flux:checkbox wire:key="event-family-member-{{ $familyMember->id }}"
                            value="{{ $familyMember->id }}"
                            :label="$familyMember->name.' · '.__('ui.family.age').' '.$familyMember->ageAt($event->starts_at ?? now())" />
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
                    <flux:button :href="route('customer.family-members.index')" wire:navigate>
                        {{ __('ui.actions.manage_family') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</section>
