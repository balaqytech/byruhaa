<?php

use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\EventContract;
use App\Modules\Events\Services\ContractRenderer;
use App\Modules\Events\States\Contract\AwaitingSignature;
use App\Support\ParticipantExtraFields;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

new #[Title('العقد')] class extends Component {
    public Booking $booking;
    public EventContract $contract;
    public string $signedName = '';

    /** @var array<string, mixed> */
    public array $participantExtraAnswers = [];

    public function mount(Booking $booking, EventContract $contract): void
    {
        abort_unless($booking->customer_id === Auth::guard('customer')->id(), 403);

        $this->contract = EventContract::query()
            ->whereKey($contract->id)
            ->with(['bookingFamilyMember.booking.event', 'bookingFamilyMember.familyMember'])
            ->whereHas('bookingFamilyMember.booking', fn ($query) => $query
                ->where('customer_id', Auth::guard('customer')->id())
                ->whereKey($booking->id))
            ->firstOrFail();

        $this->booking = $this->contract->bookingFamilyMember->booking;
        $this->signedName = Auth::guard('customer')->user()->name;
        $this->participantExtraAnswers = $this->contract->participant_extra_answers ?? [];
    }

    public function signContract(string $signatureDataUrl): void
    {
        Auth::guard('customer')->user()->ensureProfileIsComplete();

        $contract = $this->ownedContract();

        abort_unless($contract->state instanceof AwaitingSignature, 403);

        $participantExtraFields = ParticipantExtraFields::normalizeFields($contract->bookingFamilyMember->booking->event->participant_extra_fields);

        validator([
            'signedName' => $this->signedName,
            'signatureDataUrl' => $signatureDataUrl,
            'participantExtraAnswers' => $this->participantExtraAnswers,
        ], [
            'signedName' => ['required', 'string', 'max:255'],
            'signatureDataUrl' => ['required', 'string'],
            ...ParticipantExtraFields::validationRules($participantExtraFields, 'participantExtraAnswers'),
        ])->validate();

        if ($participantExtraFields !== []) {
            $contract->forceFill([
                'participant_extra_answers' => ParticipantExtraFields::answersForStorage($participantExtraFields, $this->participantExtraAnswers),
                'participant_extra_completed_at' => now(),
            ])->save();
        }

        $contract->sign($signatureDataUrl, $this->signedName, request()->ip());

        $this->refreshContract();

        Flux::toast(variant: 'success', text: __('ui.messages.contract_signed'));
    }

    public function downloadContract(ContractRenderer $contractRenderer): StreamedResponse
    {
        $contract = $this->ownedContract();

        abort_if($contract->state instanceof AwaitingSignature, 403);

        $filename = 'byruhaa-contract-'.$this->booking->reference.'-'.$contract->id.'.pdf';

        return response()->streamDownload(function () use ($contract, $contractRenderer): void {
            echo $contractRenderer->pdf($contract);
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function ownedContract(): EventContract
    {
        return EventContract::query()
            ->whereKey($this->contract->id)
            ->with(['bookingFamilyMember.booking.event', 'bookingFamilyMember.familyMember'])
            ->whereHas('bookingFamilyMember.booking', fn ($query) => $query
                ->where('customer_id', Auth::guard('customer')->id())
                ->whereKey($this->booking->id))
            ->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'familyMember' => $this->contract->bookingFamilyMember->familyMember,
            'participantExtraFields' => ParticipantExtraFields::normalizeFields($this->booking->event->participant_extra_fields),
        ];
    }

    private function refreshContract(): void
    {
        $this->contract->refresh()->load(['bookingFamilyMember.booking.event', 'bookingFamilyMember.familyMember']);
        $this->booking = $this->contract->bookingFamilyMember->booking;
        $this->participantExtraAnswers = $this->contract->participant_extra_answers ?? $this->participantExtraAnswers;
    }
}; ?>

<section class="flex flex-col gap-6">
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div class="space-y-2">
            <flux:button :href="route('customer.bookings.show', $booking)" wire:navigate variant="ghost" size="sm">
                <x-hugeicon name="arrow-left-02" class="text-base" />
                {{ __('ui.actions.view_bookings') }}
            </flux:button>

            <div>
                <flux:heading size="xl">{{ $familyMember->name }}</flux:heading>
                <flux:subheading>{{ $booking->reference }} - {{ $booking->event->name }}</flux:subheading>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <x-status-badge :state="$contract->state" />

            @if ($contract->signed_at)
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-900 dark:bg-emerald-300/10 dark:text-emerald-50">
                    {{ __('ui.bookings.signature') }}:
                    <span dir="ltr">{{ $contract->signed_at->format('Y-m-d H:i') }}</span>
                </span>
            @endif
        </div>
    </div>

    <div class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
        <div class="mb-4 flex items-center gap-2 text-emerald-800 dark:text-emerald-100">
            <x-hugeicon name="file-view" class="text-xl" />
            <flux:heading>{{ __('ui.bookings.contract_terms') }}</flux:heading>
        </div>

        <div class="prose prose-zinc max-w-none text-sm leading-7 text-emerald-950 prose-headings:text-emerald-800 prose-p:my-2 prose-ul:my-2 dark:prose-invert dark:text-emerald-50/90" dir="rtl">
            {!! $contract->contract_html !!}
        </div>

        @include('contracts.participant-extra-answers', ['contract' => $contract])
    </div>

    @if ($contract->state instanceof AwaitingSignature)
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
                    $wire.signContract(signatureDataUrl);
                }
            }"
            class="space-y-4 rounded-2xl border border-amber-200 bg-white p-5 shadow-sm dark:border-amber-300/20 dark:bg-white/5"
        >
            <div class="flex items-center gap-2 text-amber-800 dark:text-amber-100">
                <x-hugeicon name="signature" class="text-xl" />
                <flux:heading>{{ __('ui.actions.sign_contract') }}</flux:heading>
            </div>

            <flux:error name="profile" />

            @if ($participantExtraFields !== [])
                <div class="space-y-4 rounded-xl border border-sky-200 bg-sky-50/60 p-4 dark:border-sky-300/20 dark:bg-sky-300/10">
                    <div>
                        <flux:heading class="text-base">{{ __('ui.participant_extra.heading') }}</flux:heading>
                        <flux:text>{{ __('ui.participant_extra.signing_help') }}</flux:text>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        @foreach ($participantExtraFields as $field)
                            @php
                                $answerPath = "participantExtraAnswers.{$field['key']}";
                                $label = $field['required'] ? $field['label'].' *' : $field['label'];
                            @endphp

                            <div wire:key="participant-extra-{{ $contract->id }}-{{ $field['key'] }}" @class(['md:col-span-2' => in_array($field['type'], ['textarea', 'radio'], true)])>
                                @switch($field['type'])
                                    @case('textarea')
                                        <flux:textarea wire:model="{{ $answerPath }}" :label="$label" :placeholder="$field['placeholder']" />
                                        @break

                                    @case('select')
                                        <flux:select wire:model="{{ $answerPath }}" :label="$label">
                                            <option value="">{{ $field['placeholder'] ?: __('ui.participant_extra.select_placeholder') }}</option>
                                            @foreach ($field['options'] as $option)
                                                <option value="{{ $option }}">{{ $option }}</option>
                                            @endforeach
                                        </flux:select>
                                        @break

                                    @case('radio')
                                        <flux:radio.group wire:model="{{ $answerPath }}" :label="$label">
                                            @foreach ($field['options'] as $option)
                                                <flux:radio wire:key="participant-extra-radio-{{ $contract->id }}-{{ $field['key'] }}-{{ md5($option) }}" value="{{ $option }}" :label="$option" />
                                            @endforeach
                                        </flux:radio.group>
                                        @break

                                    @case('checkbox')
                                        <flux:checkbox wire:model="{{ $answerPath }}" :label="$label" />
                                        @break

                                    @case('date')
                                        <flux:input wire:model="{{ $answerPath }}" :label="$label" :placeholder="$field['placeholder']" type="date" />
                                        @break

                                    @case('number')
                                        <flux:input wire:model="{{ $answerPath }}" :label="$label" :placeholder="$field['placeholder']" type="number" />
                                        @break

                                    @default
                                        <flux:input wire:model="{{ $answerPath }}" :label="$label" :placeholder="$field['placeholder']" />
                                @endswitch

                                @if ($field['help_text'])
                                    <flux:text class="mt-1 text-xs">{{ $field['help_text'] }}</flux:text>
                                @endif

                                <flux:error :name="$answerPath" />
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

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
    @else
        <div class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="space-y-1">
                    <flux:heading>{{ __('ui.bookings.signature') }}</flux:heading>
                    <flux:text>{{ __('ui.bookings.signer_name') }}: {{ $contract->signed_name }}</flux:text>
                    <flux:text>
                        {{ __('ui.bookings.signature') }}:
                        <span dir="ltr">{{ $contract->signed_at?->format('Y-m-d H:i') }}</span>
                    </flux:text>
                </div>

                <flux:button wire:click="downloadContract" wire:target="downloadContract" variant="primary">
                    <x-hugeicon name="download-01" class="text-lg" />
                    {{ __('ui.actions.download_pdf') }}
                </flux:button>
            </div>
        </div>
    @endif
</section>
