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
    <div class="rounded-2xl bg-emerald-900 p-6 text-white shadow-sm md:p-8">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <flux:heading size="xl" class="text-white">{{ $booking->event->name }}</flux:heading>
                <flux:text class="mt-2 text-emerald-50">{{ $booking->reference }} · {{ $booking->state->label() }}</flux:text>
            </div>
            <a href="{{ route('bookings.index') }}" wire:navigate class="inline-flex min-h-10 items-center rounded-lg px-4 py-2 text-sm font-medium text-emerald-50 transition hover:bg-white/10 hover:text-white">
                {{ __('ui.actions.view_bookings') }}
            </a>
        </div>
    </div>

    <div class="grid gap-3 md:grid-cols-3">
        <div class="rounded-2xl border border-emerald-900/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
            <flux:text>{{ __('ui.labels.event') }}</flux:text>
            <flux:heading>{{ $booking->event->name }}</flux:heading>
        </div>
        <div class="rounded-2xl border border-emerald-900/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
            <flux:text>{{ __('ui.labels.status') }}</flux:text>
            <flux:heading>{{ $booking->state->label() }}</flux:heading>
        </div>
        <div class="rounded-2xl border border-emerald-900/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
            <flux:text>{{ __('ui.bookings.submitted') }}</flux:text>
            <flux:heading dir="ltr">{{ $booking->created_at->format('Y-m-d') }}</flux:heading>
        </div>
    </div>

    <div class="space-y-4">
        @foreach ($booking->familyMembers as $bookingFamilyMember)
            <div wire:key="booking-family-member-{{ $bookingFamilyMember->id }}" class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
                <div class="space-y-4">
                    <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
                        <div>
                            <flux:heading>{{ $bookingFamilyMember->familyMember->name }}</flux:heading>
                            <flux:text>{{ __('ui.family.age') }} {{ $bookingFamilyMember->familyMember->ageAt($booking->event->starts_at ?? now()) }}</flux:text>
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
                            class="space-y-4"
                        >
                            <flux:input wire:model="signedName" :label="__('ui.bookings.signer_name')" required />
                            <div>
                                <flux:text class="mb-2">{{ __('ui.bookings.signature') }}</flux:text>
                                <canvas x-ref="canvas" width="520" height="160" class="h-40 w-full rounded-xl border border-emerald-900/20 bg-white"></canvas>
                                <flux:error name="signatureDataUrl" />
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <flux:button type="submit" variant="primary" icon="pencil-square">
                                    {{ __('ui.actions.sign_contract') }}
                                </flux:button>
                                <flux:button type="button" x-on:click="clear()">
                                    {{ __('ui.actions.clear') }}
                                </flux:button>
                            </div>
                        </form>
                    @elseif ($bookingFamilyMember->contract)
                        <flux:button wire:click="downloadContract({{ $bookingFamilyMember->contract->id }})" icon="arrow-down-tray">
                            {{ __('ui.actions.download_contract') }}
                        </flux:button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>
