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

new #[Title('Booking details')] class extends Component {
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

        Flux::toast(variant: 'success', text: __('Contract signed.'));
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

<section class="mx-auto flex w-full max-w-6xl flex-col gap-6">
    <div>
        <flux:heading size="xl">{{ $booking->event->name }}</flux:heading>
        <flux:subheading>{{ $booking->reference }} · {{ $booking->state->label() }}</flux:subheading>
    </div>

    <flux:card>
        <div class="grid gap-3 md:grid-cols-3">
            <div>
                <flux:text>{{ __('Event') }}</flux:text>
                <flux:heading>{{ $booking->event->name }}</flux:heading>
            </div>
            <div>
                <flux:text>{{ __('Status') }}</flux:text>
                <flux:heading>{{ $booking->state->label() }}</flux:heading>
            </div>
            <div>
                <flux:text>{{ __('Submitted') }}</flux:text>
                <flux:heading>{{ $booking->created_at->format('Y-m-d') }}</flux:heading>
            </div>
        </div>
    </flux:card>

    <div class="space-y-4">
        @foreach ($booking->familyMembers as $bookingFamilyMember)
            <flux:card wire:key="booking-family-member-{{ $bookingFamilyMember->id }}">
                <div class="space-y-4">
                    <div class="flex flex-col justify-between gap-3 md:flex-row md:items-center">
                        <div>
                            <flux:heading>{{ $bookingFamilyMember->familyMember->name }}</flux:heading>
                            <flux:text>{{ __('Age') }} {{ $bookingFamilyMember->familyMember->ageAt($booking->event->starts_at ?? now()) }}</flux:text>
                        </div>

                        @if ($bookingFamilyMember->contract)
                            <flux:badge>{{ $bookingFamilyMember->contract->state->label() }}</flux:badge>
                        @elseif ($booking->state instanceof Approved)
                            <flux:badge>{{ __('Contract pending') }}</flux:badge>
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
                            <flux:input wire:model="signedName" :label="__('Signer name')" required />
                            <div>
                                <flux:text class="mb-2">{{ __('Signature') }}</flux:text>
                                <canvas x-ref="canvas" width="520" height="160" class="h-40 w-full rounded-lg border border-zinc-300 bg-white"></canvas>
                                <flux:error name="signatureDataUrl" />
                            </div>
                            <div class="flex gap-3">
                                <flux:button type="submit" variant="primary" icon="pencil-square">
                                    {{ __('Sign contract') }}
                                </flux:button>
                                <flux:button type="button" x-on:click="clear()">
                                    {{ __('Clear') }}
                                </flux:button>
                            </div>
                        </form>
                    @elseif ($bookingFamilyMember->contract)
                        <flux:button wire:click="downloadContract({{ $bookingFamilyMember->contract->id }})" icon="arrow-down-tray">
                            {{ __('Download contract') }}
                        </flux:button>
                    @endif
                </div>
            </flux:card>
        @endforeach
    </div>
</section>
