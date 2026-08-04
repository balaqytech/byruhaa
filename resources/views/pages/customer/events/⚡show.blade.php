<?php

use App\Actions\CalculateBookingPrice;
use App\Actions\CreateCustomerBooking;
use App\Actions\ExpressEventInterest;
use App\Data\BookingPriceSnapshot;
use App\Enums\EventStatus;
use App\Enums\EventInterestSource;
use App\Models\Discount;
use App\Models\Event;
use App\Models\EventPaymentPlan;
use App\Models\EventPaymentPlanInstallment;
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

    public ?string $couponCode = null;

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
            'couponCode' => ['nullable', 'string', 'max:255'],
        ]);

        $booking = $createCustomerBooking->execute(
            $customer,
            [
                'event_id' => $this->event->id,
                'family_member_ids' => $validated['familyMemberIds'],
                'coupon_code' => $validated['couponCode'] ?? null,
            ],
            $affiliateAttribution->current(),
        );

        Flux::toast(variant: 'success', text: __('ui.messages.booking_request_submitted'));

        $this->redirectRoute('customer.bookings.show', $booking, navigate: true);
    }

    public function expressInterest(ExpressEventInterest $expressEventInterest): void
    {
        $expressEventInterest->execute(Auth::guard('customer')->user(), $this->event, [
            'preferred_contact_channel' => 'whatsapp',
            'contact_consent' => true,
        ], EventInterestSource::Website);

        Flux::toast(variant: 'success', text: 'تم تسجيل اهتمامك وسنخبرك عند فتح الحجز.');
    }

    public function with(): array
    {
        $priceSnapshot = $this->priceSnapshot();
        $paymentPlans = $this->paymentPlans();

        return [
            'availableDiscounts' => $this->availableDiscounts(),
            'familyMembers' => Auth::guard('customer')->user()->familyMembers()->oldest('birth_date')->get(),
            'paymentPlans' => $paymentPlans,
            'paymentPlanPreviews' => $this->paymentPlanPreviews($paymentPlans, $priceSnapshot->totalBaisa),
            'priceSnapshot' => $priceSnapshot,
            'selectedFamilyMemberCount' => count(array_unique($this->familyMemberIds)),
        ];
    }

    /**
     * @return EloquentCollection<int, Discount>
     */
    private function availableDiscounts(): EloquentCollection
    {
        return Discount::query()->availableForEvent($this->event)->orderByDesc('amount_baisa')->orderBy('id')->get();
    }

    private function priceSnapshot(): BookingPriceSnapshot
    {
        return app(CalculateBookingPrice::class)->execute(
            $this->event,
            count(array_unique($this->familyMemberIds)),
            couponCode: $this->couponCode,
        );
    }

    /**
     * @return EloquentCollection<int, EventPaymentPlan>
     */
    private function paymentPlans(): EloquentCollection
    {
        return $this->event
            ->paymentPlans()
            ->where('is_active', true)
            ->with('installments')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  EloquentCollection<int, EventPaymentPlan>  $paymentPlans
     * @return array<int, array<int, array{name: string, percentage: int, due_date: string|null, amount_baisa: int}>>
     */
    private function paymentPlanPreviews(EloquentCollection $paymentPlans, int $totalBaisa): array
    {
        return $paymentPlans
            ->mapWithKeys(function (EventPaymentPlan $paymentPlan) use ($totalBaisa): array {
                $installments = $paymentPlan->installments->values();

                if ($installments->isEmpty() || (int) $installments->sum('percentage') !== 100) {
                    return [$paymentPlan->id => []];
                }

                $remainingBaisa = $totalBaisa;
                $rows = $installments->map(function (EventPaymentPlanInstallment $installment, int $index) use ($installments, $totalBaisa, &$remainingBaisa): array {
                    $amountBaisa = $index === $installments->count() - 1
                        ? $remainingBaisa
                        : intdiv($totalBaisa * $installment->percentage, 100);
                    $remainingBaisa -= $amountBaisa;

                    return [
                        'name' => $installment->name ?: __('ui.payments.installment_number', ['number' => $installment->sequence]),
                        'percentage' => $installment->percentage,
                        'due_date' => $installment->due_date?->format('Y-m-d'),
                        'amount_baisa' => $amountBaisa,
                    ];
                })->all();

                return [$paymentPlan->id => $rows];
            })
            ->all();
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
                <x-money :amount-baisa="$priceSnapshot->unitPriceBaisa" :currency="$event->currency" class="text-2xl font-semibold text-white" />
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
            @if ($event->canBook())
            <form wire:submit="book" class="overflow-hidden rounded-2xl border border-emerald-900/10 bg-white shadow-sm dark:border-white/10 dark:bg-white/5">
                <div class="border-b border-emerald-900/10 bg-emerald-950 p-5 text-white dark:border-white/10">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <flux:heading class="text-white">{{ __('ui.events.checkout_title') }}</flux:heading>
                            <flux:text class="text-emerald-50">{{ __('ui.events.checkout_subheading') }}</flux:text>
                        </div>
                        <span class="flex size-11 items-center justify-center rounded-xl bg-amber-300/15 text-amber-100">
                            <x-hugeicon name="wallet-02" class="text-2xl" />
                        </span>
                    </div>
                </div>

                <div class="space-y-5 p-5">
                    <div class="rounded-xl border border-emerald-900/10 bg-emerald-50/60 p-4 dark:border-white/10 dark:bg-white/5">
                        <flux:checkbox.group wire:model.live="familyMemberIds" :label="__('ui.events.family_members')">
                            @forelse ($familyMembers as $familyMember)
                                <flux:checkbox
                                    wire:key="event-family-member-{{ $familyMember->id }}"
                                    value="{{ $familyMember->id }}"
                                    :label="$familyMember->name.' · '.__('ui.family.age').' '.$familyMember->ageAt($event->starts_at ?? now())"
                                />
                            @empty
                                <flux:text>{{ __('ui.events.add_family_before_booking') }}</flux:text>
                            @endforelse
                        </flux:checkbox.group>

                        <flux:error name="familyMemberIds" />
                    </div>

                    <flux:field>
                        <flux:label>{{ __('ui.events.coupon_code') }}</flux:label>
                        <div class="flex gap-2">
                            <flux:input wire:model.live.debounce.500ms="couponCode" placeholder="{{ __('ui.events.coupon_code_placeholder') }}" />
                            <flux:button type="button" wire:click="$set('couponCode', null)">
                                {{ __('ui.actions.clear') }}
                            </flux:button>
                        </div>
                        <flux:error name="couponCode" />
                        <flux:error name="coupon_code" />
                    </flux:field>

                    <section class="rounded-xl border border-emerald-900/10 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-center justify-between gap-3">
                            <flux:heading class="text-base">{{ __('ui.events.order_summary') }}</flux:heading>
                            <flux:badge color="emerald">{{ trans_choice('ui.bookings.family_member_count', $selectedFamilyMemberCount, ['count' => $selectedFamilyMemberCount]) }}</flux:badge>
                        </div>

                        <dl class="mt-4 grid gap-3 text-sm">
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-zinc-600 dark:text-zinc-300">{{ __('ui.events.price_per_family_member') }}</dt>
                                <dd class="font-semibold text-emerald-950 dark:text-emerald-50">
                                    <x-money :amount-baisa="$priceSnapshot->unitPriceBaisa" :currency="$priceSnapshot->currency" />
                                </dd>
                            </div>

                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-zinc-600 dark:text-zinc-300">{{ __('ui.payments.subtotal') }}</dt>
                                <dd class="font-semibold text-emerald-950 dark:text-emerald-50">
                                    <x-money :amount-baisa="$priceSnapshot->subtotalBaisa" :currency="$priceSnapshot->currency" />
                                </dd>
                            </div>

                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-zinc-600 dark:text-zinc-300">{{ __('ui.payments.discount') }}</dt>
                                <dd class="font-semibold text-amber-700 dark:text-amber-200">
                                    <x-money :amount-baisa="$priceSnapshot->discountAmountBaisa" :currency="$priceSnapshot->currency" />
                                </dd>
                            </div>

                            @if ($priceSnapshot->discountName)
                                <div class="rounded-lg bg-amber-50 px-3 py-2 text-sm font-medium text-amber-900 dark:bg-amber-300/10 dark:text-amber-100">
                                    {{ __('ui.events.applied_discount') }}: {{ $priceSnapshot->discountName }}
                                    @if ($priceSnapshot->couponCode)
                                        <span dir="ltr">({{ $priceSnapshot->couponCode }})</span>
                                    @endif
                                </div>
                            @endif

                            <div class="flex items-center justify-between gap-4 border-t border-emerald-900/10 pt-3 dark:border-white/10">
                                <dt class="font-semibold text-emerald-950 dark:text-emerald-50">{{ __('ui.payments.total') }}</dt>
                                <dd class="text-2xl font-bold text-emerald-700 dark:text-emerald-200">
                                    <x-money :amount-baisa="$priceSnapshot->totalBaisa" :currency="$priceSnapshot->currency" />
                                </dd>
                            </div>
                        </dl>
                    </section>

                    @if ($paymentPlans->isNotEmpty())
                        <section class="rounded-xl border border-emerald-900/10 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <flux:heading class="text-base">{{ __('ui.events.payment_plan_preview') }}</flux:heading>
                                <x-hugeicon name="payment-02" class="text-xl text-emerald-700 dark:text-emerald-200" />
                            </div>

                            <div class="mb-3 flex items-center justify-between gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm dark:border-emerald-300/20 dark:bg-emerald-300/10">
                                <div>
                                    <p class="font-medium text-emerald-950 dark:text-emerald-50">{{ __('ui.payments.full_payment') }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('ui.payments.full_payment_description') }}</p>
                                </div>
                                <x-money :amount-baisa="$priceSnapshot->totalBaisa" :currency="$priceSnapshot->currency" class="font-semibold text-emerald-700 dark:text-emerald-200" />
                            </div>

                            <div class="grid gap-3">
                                @foreach ($paymentPlans as $paymentPlan)
                                    @php($previewRows = $paymentPlanPreviews[$paymentPlan->id] ?? [])

                                    <div class="rounded-lg border border-emerald-900/10 p-3 dark:border-white/10">
                                        <p class="font-semibold text-emerald-950 dark:text-emerald-50">{{ $paymentPlan->name }}</p>

                                        @if ($previewRows === [])
                                            <flux:text class="mt-2 text-sm">{{ __('ui.events.payment_plan_preview_empty') }}</flux:text>
                                        @else
                                            <div class="mt-3 grid gap-2">
                                                @foreach ($previewRows as $row)
                                                    <div class="flex items-center justify-between gap-3 rounded-lg bg-emerald-50/60 px-3 py-2 text-sm dark:bg-white/5">
                                                        <div>
                                                            <p class="font-medium text-emerald-950 dark:text-emerald-50">{{ $row['name'] }}</p>
                                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $row['percentage'] }}%@if ($row['due_date']) · <span dir="ltr">{{ $row['due_date'] }}</span>@endif</p>
                                                        </div>
                                                        <x-money :amount-baisa="$row['amount_baisa']" :currency="$priceSnapshot->currency" class="font-semibold text-emerald-700 dark:text-emerald-200" />
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <div class="flex flex-wrap gap-3">
                        <flux:button type="submit" variant="primary" :disabled="$familyMembers->isEmpty()">
                            <x-hugeicon name="check-list" class="text-lg" />
                            {{ __('ui.actions.submit_request') }}
                        </flux:button>
                        <flux:button :href="route('customer.family-members.index')" wire:navigate>
                            {{ __('ui.actions.manage_family') }}
                        </flux:button>
                    </div>
                </div>
            </form>
            @elseif ($event->canExpressInterest())
                <section class="rounded-2xl border border-amber-300 bg-amber-50 p-6 dark:border-amber-300/20 dark:bg-amber-300/10">
                    <flux:heading size="lg">الحجز لم يفتح بعد</flux:heading>
                    <flux:text class="mt-2">سجّل اهتمامك وسنتواصل معك عند فتح الحجز أو تحديث موعد الفعالية.</flux:text>
                    <flux:button class="mt-5" variant="primary" wire:click="expressInterest">
                        <x-hugeicon name="notification-02" class="text-lg" />
                        أبدِ اهتمامك
                    </flux:button>
                </section>
            @else
                <section class="rounded-2xl border border-emerald-900/10 bg-white p-6 dark:border-white/10 dark:bg-white/5">
                    <flux:heading size="lg">{{ $event->enrollment_status->getLabel() }}</flux:heading>
                    <flux:text class="mt-2">لا تتوفر إجراءات تسجيل لهذه الفعالية حاليًا.</flux:text>
                </section>
            @endif
        </div>
    </div>
</section>
