<?php

use App\Models\Booking;
use App\Models\EventContract;
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
    public string $signatureDataUrl = '';

    public function mount(Booking $booking): void
    {
        abort_unless($booking->customer_id === Auth::guard('customer')->id(), 403);

        $this->booking = $booking->load(['event', 'familyMembers.familyMember', 'familyMembers.contract']);
        $this->signedName = Auth::guard('customer')->user()->name;
    }

    public function signContract(int $contractId): void
    {
        $contract = $this->ownedContract($contractId);

        abort_unless($contract->state instanceof AwaitingSignature, 403);

        $this->validate([
            'signedName' => ['required', 'string', 'max:255'],
            'signatureDataUrl' => ['required', 'string'],
        ]);

        $contract->sign($this->signatureDataUrl, $this->signedName, request()->ip());

        $this->signatureDataUrl = '';
        $this->booking->refresh()->load(['event', 'familyMembers.familyMember', 'familyMembers.contract']);

        Flux::toast(variant: 'success', text: __('ui.messages.contract_signed'));
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
                            <flux:badge>{{ $bookingFamilyMember->contract->state->label() }}</flux:badge>
                        @elseif ($booking->state instanceof Approved)
                            <flux:badge>{{ __('ui.bookings.contract_pending') }}</flux:badge>
                        @endif
                    </div>

                    @if ($bookingFamilyMember->contract && $bookingFamilyMember->contract->state instanceof AwaitingSignature)
                        <form
                            wire:submit="signContract({{ $bookingFamilyMember->contract->id }})"
                            x-data="{
                                drawing: false,
                                init() {
                                    const canvas = this.$refs.canvas;
                                    const ctx = canvas.getContext('2d');
                                    ctx.lineWidth = 2;
                                    ctx.lineCap = 'round';
                                    const point = (event) => {
                                        const rect = canvas.getBoundingClientRect();
                                        return { x: event.offsetX ?? event.touches[0].clientX - rect.left, y: event.offsetY ?? event.touches[0].clientY - rect.top };
                                    };
                                    canvas.addEventListener('mousedown', event => { this.drawing = true; const p = point(event); ctx.beginPath(); ctx.moveTo(p.x, p.y); });
                                    canvas.addEventListener('mousemove', event => { if (! this.drawing) return; const p = point(event); ctx.lineTo(p.x, p.y); ctx.stroke(); });
                                    window.addEventListener('mouseup', () => this.drawing = false);
                                    canvas.addEventListener('touchstart', event => { event.preventDefault(); this.drawing = true; const p = point(event); ctx.beginPath(); ctx.moveTo(p.x, p.y); });
                                    canvas.addEventListener('touchmove', event => { event.preventDefault(); if (! this.drawing) return; const p = point(event); ctx.lineTo(p.x, p.y); ctx.stroke(); });
                                    window.addEventListener('touchend', () => this.drawing = false);
                                },
                                clear() {
                                    const canvas = this.$refs.canvas;
                                    canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
                                    $wire.set('signatureDataUrl', '');
                                },
                                capture() {
                                    $wire.set('signatureDataUrl', this.$refs.canvas.toDataURL('image/png'));
                                }
                            }"
                            x-on:submit="capture()"
                            class="mt-5 space-y-4 rounded-xl border border-amber-200 bg-white p-4 dark:border-amber-300/20 dark:bg-white/5"
                        >
                            <div class="flex items-center gap-2 text-amber-800 dark:text-amber-100">
                                <x-hugeicon name="signature" class="text-xl" />
                                <flux:heading class="text-base">{{ __('ui.actions.sign_contract') }}</flux:heading>
                            </div>

                            <flux:input wire:model="signedName" :label="__('ui.bookings.signer_name')" required />
                            <div>
                                <flux:text class="mb-2">{{ __('ui.bookings.signature') }}</flux:text>
                                <canvas x-ref="canvas" width="520" height="160" class="h-40 w-full rounded-xl border border-emerald-900/20 bg-white"></canvas>
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
