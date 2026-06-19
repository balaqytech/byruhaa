<?php

use App\Actions\InitiateInstallmentPayment;
use App\Actions\SelectBookingPaymentPlan;
use App\Enums\BookingInstallmentState;
use App\Models\Booking;
use App\Models\BookingInstallment;
use App\Models\EventContract;
use App\Models\EventPaymentPlan;
use App\Services\ContractRenderer;
use App\States\Booking\Approved;
use App\States\Contract\AwaitingSignature;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('تفاصيل الحجز')] class extends Component {
    public Booking $booking;
    public string $signedName = '';
    public ?int $paymentPlanId = null;

    public function mount(Booking $booking): void
    {
        abort_unless($booking->customer_id === Auth::guard('customer')->id(), 403);

        $this->booking = $booking->load(['event', 'familyMembers.familyMember', 'familyMembers.contract', 'paymentSchedule.installments']);
        $this->signedName = Auth::guard('customer')->user()->name;
    }

    public function signContract(int $contractId, string $signatureDataUrl): void
    {
        $contract = $this->ownedContract($contractId);

        abort_unless($contract->state instanceof AwaitingSignature, 403);

        validator([
            'signedName' => $this->signedName,
            'signatureDataUrl' => $signatureDataUrl,
        ], [
            'signedName' => ['required', 'string', 'max:255'],
            'signatureDataUrl' => ['required', 'string'],
        ])->validate();

        $contract->sign($signatureDataUrl, $this->signedName, request()->ip());

        $this->refreshBooking();

        Flux::toast(variant: 'success', text: __('ui.messages.contract_signed'));
    }

    public function selectPaymentPlan(SelectBookingPaymentPlan $selectBookingPaymentPlan): void
    {
        $validated = $this->validate([
            'paymentPlanId' => ['required', 'integer'],
        ]);

        $paymentPlan = EventPaymentPlan::query()
            ->whereKey($validated['paymentPlanId'])
            ->firstOrFail();

        $selectBookingPaymentPlan->execute($this->booking, $paymentPlan);

        $this->paymentPlanId = null;
        $this->refreshBooking();

        Flux::toast(variant: 'success', text: __('ui.messages.payment_plan_selected'));
    }

    public function payInstallment(int $installmentId, InitiateInstallmentPayment $initiateInstallmentPayment)
    {
        $installment = BookingInstallment::query()
            ->whereKey($installmentId)
            ->whereHas('paymentSchedule.booking', fn ($query) => $query
                ->where('customer_id', Auth::guard('customer')->id())
                ->where('id', $this->booking->id))
            ->firstOrFail();

        $payment = $initiateInstallmentPayment->execute($installment, (int) Auth::guard('customer')->id());

        return redirect()->away((string) $payment->checkout_url);
    }

    public function downloadContract(int $contractId, ContractRenderer $contractRenderer)
    {
        $contract = $this->ownedContract($contractId);

        abort_unless(! $contract->state instanceof AwaitingSignature, 403);

        $filename = 'byruhaa-contract-'.$this->booking->reference.'-'.$contract->id.'.pdf';

        return response()->streamDownload(function () use ($contract, $contractRenderer): void {
            echo $contractRenderer->pdf($contract);
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function ownedContract(int $contractId): EventContract
    {
        return EventContract::query()
            ->whereKey($contractId)
            ->whereHas('bookingFamilyMember.booking', fn ($query) => $query
                ->where('customer_id', Auth::guard('customer')->id())
                ->where('id', $this->booking->id))
            ->firstOrFail();
    }

    public function with(): array
    {
        return [
            'activePaymentPlans' => $this->booking->event
                ->paymentPlans()
                ->where('is_active', true)
                ->with('installments')
                ->oldest('name')
                ->get(),
            'contractsAreSigned' => $this->booking->hasSignedContracts(),
            'payableInstallmentId' => $this->booking->paymentSchedule?->installments
                ->first(fn (BookingInstallment $installment): bool => $installment->state === BookingInstallmentState::Pending)
                ?->id,
        ];
    }

    private function refreshBooking(): void
    {
        $this->booking->refresh()->load(['event', 'familyMembers.familyMember', 'familyMembers.contract', 'paymentSchedule.installments']);
    }
}; ?>

<section class="flex flex-col gap-6">
    <div class="overflow-hidden rounded-2xl bg-emerald-900 text-white shadow-sm">
        <div class="p-6 md:p-8">
            <div class="flex flex-col gap-5 md:flex-row md:items-end md:justify-between">
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-2 rounded-full border border-amber-300/30 bg-amber-300/15 px-3 py-1 text-sm font-medium text-amber-100">
                            <x-hugeicon name="contracts" class="text-lg" />
                            {{ $booking->reference }}
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-sm font-medium text-emerald-50">
                            <x-hugeicon name="checkmark-badge-01" class="text-lg" />
                            {{ $booking->state->label() }}
                        </span>
                    </div>

                    <div>
                        <flux:heading size="xl" class="text-white">{{ $booking->event->name }}</flux:heading>
                        <flux:text class="mt-2 text-emerald-50">
                            {{ __('ui.bookings.submitted') }}
                            <span dir="ltr">{{ $booking->created_at->format('Y-m-d') }}</span>
                        </flux:text>
                    </div>
                </div>

                <flux:button :href="route('bookings.index')" wire:navigate variant="ghost" class="text-emerald-50 hover:bg-white/10 hover:text-white">
                    <x-hugeicon name="arrow-left-02" class="text-lg" />
                    {{ __('ui.actions.view_bookings') }}
                </flux:button>
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-xl border border-emerald-900/10 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900 dark:border-white/10 dark:bg-emerald-300/10 dark:text-emerald-50">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid gap-3 md:grid-cols-4">
        <div class="rounded-xl border border-emerald-900/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-200">
                    <x-hugeicon name="calendar-03" class="text-xl" />
                </span>
                <div>
                    <flux:text>{{ __('ui.labels.event') }}</flux:text>
                    <flux:heading class="text-base">{{ $booking->event->name }}</flux:heading>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-emerald-900/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl bg-sky-100 text-sky-800 dark:bg-sky-300/15 dark:text-sky-200">
                    <x-hugeicon name="checkmark-badge-01" class="text-xl" />
                </span>
                <div>
                    <flux:text>{{ __('ui.labels.status') }}</flux:text>
                    <flux:heading class="text-base">{{ $booking->state->label() }}</flux:heading>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-emerald-900/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl bg-amber-100 text-amber-800 dark:bg-amber-300/15 dark:text-amber-100">
                    <x-hugeicon name="user-group" class="text-xl" />
                </span>
                <div>
                    <flux:text>{{ __('ui.events.family_members') }}</flux:text>
                    <flux:heading class="text-base">{{ trans_choice('ui.bookings.family_member_count', $booking->familyMembers->count(), ['count' => $booking->familyMembers->count()]) }}</flux:heading>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-emerald-900/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl bg-rose-100 text-rose-800 dark:bg-rose-300/15 dark:text-rose-100">
                    <x-hugeicon name="clock-01" class="text-xl" />
                </span>
                <div>
                    <flux:text>{{ __('ui.bookings.submitted') }}</flux:text>
                    <flux:heading class="text-base" dir="ltr">{{ $booking->created_at->format('Y-m-d') }}</flux:heading>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
        <div class="mb-5 flex items-center justify-between gap-3">
            <div>
                <flux:heading>{{ __('ui.payments.heading') }}</flux:heading>
                <flux:text>{{ __('ui.payments.subheading') }}</flux:text>
            </div>
            <span class="flex size-11 items-center justify-center rounded-xl bg-sky-100 text-sky-800 dark:bg-sky-300/15 dark:text-sky-100">
                <x-hugeicon name="wallet-02" class="text-2xl" />
            </span>
        </div>

        <div class="grid gap-3 md:grid-cols-4">
            <div class="rounded-xl border border-emerald-900/10 bg-emerald-50/40 p-4 dark:border-white/10 dark:bg-white/5">
                <flux:text>{{ __('ui.payments.subtotal') }}</flux:text>
                <flux:heading class="text-base"><x-money :amount-baisa="$booking->subtotal_baisa" :currency="$booking->currency" /></flux:heading>
            </div>
            <div class="rounded-xl border border-emerald-900/10 bg-emerald-50/40 p-4 dark:border-white/10 dark:bg-white/5">
                <flux:text>{{ __('ui.payments.discount') }}</flux:text>
                <flux:heading class="text-base"><x-money :amount-baisa="$booking->discount_amount_baisa" :currency="$booking->currency" /></flux:heading>
            </div>
            <div class="rounded-xl border border-emerald-900/10 bg-emerald-50/40 p-4 dark:border-white/10 dark:bg-white/5">
                <flux:text>{{ __('ui.payments.total') }}</flux:text>
                <flux:heading class="text-base"><x-money :amount-baisa="$booking->total_baisa" :currency="$booking->currency" /></flux:heading>
            </div>
            <div class="rounded-xl border border-emerald-900/10 bg-emerald-50/40 p-4 dark:border-white/10 dark:bg-white/5">
                <flux:text>{{ __('ui.payments.plan') }}</flux:text>
                <flux:heading class="text-base">{{ $booking->paymentSchedule?->plan_name ?? __('ui.payments.not_selected') }}</flux:heading>
            </div>
        </div>

        @if ($booking->paymentSchedule)
            <div class="mt-5 overflow-hidden rounded-xl border border-emerald-900/10 dark:border-white/10">
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('ui.payments.installment') }}</flux:table.column>
                        <flux:table.column>{{ __('ui.payments.due_date') }}</flux:table.column>
                        <flux:table.column>{{ __('ui.payments.amount') }}</flux:table.column>
                        <flux:table.column>{{ __('ui.labels.status') }}</flux:table.column>
                        <flux:table.column>{{ __('ui.payments.payment') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($booking->paymentSchedule->installments as $installment)
                            <flux:table.row wire:key="booking-installment-{{ $installment->id }}">
                                <flux:table.cell>{{ $installment->name ?: __('ui.payments.installment_number', ['number' => $installment->sequence]) }}</flux:table.cell>
                                <flux:table.cell dir="ltr">{{ $installment->due_date->format('Y-m-d') }}</flux:table.cell>
                                <flux:table.cell><x-money :amount-baisa="$installment->amount_baisa" :currency="$booking->currency" /></flux:table.cell>
                                <flux:table.cell>
                                    <x-status-badge :state="$installment->state" />
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($installment->state === BookingInstallmentState::Pending && $installment->id === $payableInstallmentId && $installment->amount_baisa >= 100)
                                        <flux:button wire:click="payInstallment({{ $installment->id }})" wire:target="payInstallment({{ $installment->id }})" size="sm" variant="primary">
                                            <x-hugeicon name="wallet-02" class="text-lg" />
                                            {{ __('ui.payments.pay_with_thawani') }}
                                        </flux:button>
                                    @elseif ($installment->state === BookingInstallmentState::Pending)
                                        <span class="text-sm text-zinc-500 dark:text-white/60">{{ __('ui.payments.waiting_for_previous') }}</span>
                                    @else
                                        <span class="text-sm text-zinc-500 dark:text-white/60">{{ __('ui.payments.completed') }}</span>
                                    @endif
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @elseif ($booking->state instanceof Approved && $contractsAreSigned)
            @if ($activePaymentPlans->isNotEmpty())
                <form wire:submit="selectPaymentPlan" class="mt-5 space-y-4 rounded-xl border border-sky-200 bg-sky-50/60 p-4 dark:border-sky-300/20 dark:bg-sky-300/10">
                    <flux:select wire:model="paymentPlanId" :label="__('ui.payments.choose_plan')" required>
                        <option value="">{{ __('ui.payments.choose_plan_placeholder') }}</option>
                        @foreach ($activePaymentPlans as $paymentPlan)
                            <option value="{{ $paymentPlan->id }}">{{ $paymentPlan->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="paymentPlanId" />

                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach ($activePaymentPlans as $paymentPlan)
                            <div wire:key="payment-plan-option-{{ $paymentPlan->id }}" class="rounded-xl border border-sky-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                                <flux:heading class="text-base">{{ $paymentPlan->name }}</flux:heading>
                                <div class="mt-3 space-y-2">
                                    @foreach ($paymentPlan->installments as $planInstallment)
                                        <div class="flex items-center justify-between gap-3 text-sm text-emerald-950/75 dark:text-emerald-50/75">
                                            <span>{{ $planInstallment->name ?: __('ui.payments.installment_number', ['number' => $planInstallment->sequence]) }}</span>
                                            <span dir="ltr">{{ $planInstallment->percentage }}% · {{ $planInstallment->due_date->format('Y-m-d') }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <flux:button type="submit" variant="primary">
                        <x-hugeicon name="check-list" class="text-lg" />
                        {{ __('ui.payments.select_plan') }}
                    </flux:button>
                </form>
            @else
                <div class="mt-5 rounded-xl border border-dashed border-emerald-900/20 p-5 text-center dark:border-white/15">
                    <flux:text>{{ __('ui.payments.no_plans') }}</flux:text>
                </div>
            @endif
        @else
            <div class="mt-5 rounded-xl border border-dashed border-emerald-900/20 p-5 text-center dark:border-white/15">
                <flux:text>{{ __('ui.payments.available_after_contracts') }}</flux:text>
            </div>
        @endif
    </div>

    <div class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
        <div class="mb-5 flex items-center justify-between gap-3">
            <div>
                <flux:heading>{{ __('ui.events.family_members') }}</flux:heading>
                <flux:text>{{ __('ui.bookings.subheading') }}</flux:text>
            </div>
            <span class="flex size-11 items-center justify-center rounded-xl bg-emerald-100 text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-200">
                <x-hugeicon name="signature" class="text-2xl" />
            </span>
        </div>

        <div class="space-y-4">
            @foreach ($booking->familyMembers as $bookingFamilyMember)
                <div wire:key="booking-family-member-{{ $bookingFamilyMember->id }}" class="rounded-xl border border-emerald-900/10 bg-emerald-50/40 p-4 dark:border-white/10 dark:bg-white/5">
                    <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
                        <div class="flex items-start gap-3">
                            <span class="mt-1 flex size-10 items-center justify-center rounded-xl bg-white text-emerald-800 shadow-sm dark:bg-white/10 dark:text-emerald-100">
                                <x-hugeicon name="student" class="text-xl" />
                            </span>
                            <div>
                                <flux:heading>{{ $bookingFamilyMember->familyMember->name }}</flux:heading>
                                <flux:text>{{ __('ui.family.age') }} {{ $bookingFamilyMember->familyMember->ageAt($booking->event->starts_at ?? now()) }}</flux:text>
                            </div>
                        </div>

                        @if ($bookingFamilyMember->contract)
                            <x-status-badge :state="$bookingFamilyMember->contract->state" />
                        @elseif ($booking->state instanceof Approved)
                            <x-status-badge color="amber">{{ __('ui.bookings.contract_pending') }}</x-status-badge>
                        @endif
                    </div>

                    @if ($bookingFamilyMember->contract && $bookingFamilyMember->contract->state instanceof AwaitingSignature)
                        <div class="mt-5 rounded-xl border border-emerald-900/10 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                            <div class="mb-3 flex items-center gap-2 text-emerald-800 dark:text-emerald-100">
                                <x-hugeicon name="file-view" class="text-xl" />
                                <flux:heading class="text-base">{{ __('ui.bookings.contract_terms') }}</flux:heading>
                            </div>
                            <div class="prose max-w-none text-sm leading-7 text-emerald-950 prose-headings:text-emerald-800 prose-p:my-2 prose-ul:my-2 dark:prose-invert dark:text-emerald-50/90" dir="rtl">
                                {!! $bookingFamilyMember->contract->contract_html !!}
                            </div>
                        </div>

                        <form
                            x-on:submit.prevent="submitSignature"
                            x-data="{
                                drawing: false,
                                hasSignature: false,
                                context: null,
                                init() {
                                    const canvas = this.$refs.canvas;
                                    this.context = canvas.getContext('2d');
                                    this.resizeCanvas();
                                    this.context.lineWidth = 2.5;
                                    this.context.lineCap = 'round';
                                    this.context.lineJoin = 'round';
                                    this.context.strokeStyle = '#17382f';

                                    const point = (event) => {
                                        const rect = canvas.getBoundingClientRect();
                                        const source = event.touches?.[0] ?? event.changedTouches?.[0] ?? event;

                                        return {
                                            x: source.clientX - rect.left,
                                            y: source.clientY - rect.top,
                                        };
                                    };

                                    canvas.addEventListener('pointerdown', event => {
                                        event.preventDefault();
                                        canvas.setPointerCapture(event.pointerId);
                                        this.drawing = true;
                                        const p = point(event);
                                        this.context.beginPath();
                                        this.context.moveTo(p.x, p.y);
                                    });

                                    canvas.addEventListener('pointermove', event => {
                                        if (! this.drawing) return;

                                        event.preventDefault();
                                        const p = point(event);
                                        this.context.lineTo(p.x, p.y);
                                        this.context.stroke();
                                        this.hasSignature = true;
                                    });

                                    window.addEventListener('pointerup', () => this.drawing = false);
                                    window.addEventListener('resize', () => this.resizeCanvas());
                                },
                                resizeCanvas() {
                                    const canvas = this.$refs.canvas;
                                    const rect = canvas.getBoundingClientRect();
                                    const ratio = window.devicePixelRatio || 1;

                                    canvas.width = Math.max(1, Math.floor(rect.width * ratio));
                                    canvas.height = Math.max(1, Math.floor(rect.height * ratio));

                                    this.context.setTransform(ratio, 0, 0, ratio, 0, 0);
                                },
                                clear() {
                                    const canvas = this.$refs.canvas;
                                    const rect = canvas.getBoundingClientRect();
                                    this.context.clearRect(0, 0, rect.width, rect.height);
                                    this.hasSignature = false;
                                },
                                submitSignature() {
                                    const signatureDataUrl = this.hasSignature ? this.$refs.canvas.toDataURL('image/png') : '';
                                    $wire.signContract({{ $bookingFamilyMember->contract->id }}, signatureDataUrl);
                                }
                            }"
                            class="mt-5 space-y-4 rounded-xl border border-amber-200 bg-white p-4 dark:border-amber-300/20 dark:bg-white/5"
                        >
                            <div class="flex items-center gap-2 text-amber-800 dark:text-amber-100">
                                <x-hugeicon name="signature" class="text-xl" />
                                <flux:heading class="text-base">{{ __('ui.actions.sign_contract') }}</flux:heading>
                            </div>

                            <flux:input wire:model="signedName" :label="__('ui.bookings.signer_name')" required />
                            <div>
                                <flux:text class="mb-2">{{ __('ui.bookings.signature') }}</flux:text>
                                <canvas x-ref="canvas" class="h-40 w-full touch-none rounded-xl border border-emerald-900/20 bg-white"></canvas>
                                <flux:error name="signatureDataUrl" />
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <flux:button type="submit" variant="primary">
                                    <x-hugeicon name="signature" class="text-lg" />
                                    {{ __('ui.actions.sign_contract') }}
                                </flux:button>
                                <flux:button type="button" x-on:click="clear()">
                                    <x-hugeicon name="delete-02" class="text-lg" />
                                    {{ __('ui.actions.clear') }}
                                </flux:button>
                            </div>
                        </form>
                    @elseif ($bookingFamilyMember->contract)
                        <div class="mt-5 rounded-xl border border-emerald-900/10 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                            <div class="mb-3 flex items-center gap-2 text-emerald-800 dark:text-emerald-100">
                                <x-hugeicon name="file-view" class="text-xl" />
                                <flux:heading class="text-base">{{ __('ui.bookings.contract_terms') }}</flux:heading>
                            </div>
                            <div class="prose max-w-none text-sm leading-7 text-emerald-950 prose-headings:text-emerald-800 prose-p:my-2 prose-ul:my-2 dark:prose-invert dark:text-emerald-50/90" dir="rtl">
                                {!! $bookingFamilyMember->contract->contract_html !!}
                            </div>
                        </div>

                        <div class="mt-4">
                            <flux:button wire:click="downloadContract({{ $bookingFamilyMember->contract->id }})">
                                <x-hugeicon name="download-01" class="text-lg" />
                                {{ __('ui.actions.download_contract') }}
                            </flux:button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</section>
