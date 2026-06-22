<?php

use App\Actions\CreateAffiliatePayoutRequest;
use App\Models\Affiliate;
use App\Support\Money\MoneyFactory;
use Brick\Math\Exception\MathException;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::affiliate')] #[Title('Request payout')] class extends Component
{
    public string $amount = '';

    public ?string $affiliate_notes = null;

    public ?string $payment_details = null;

    public function mount(): void
    {
        $affiliate = Auth::guard('affiliate')->user();

        abort_unless($affiliate instanceof Affiliate, 403);

        $this->amount = MoneyFactory::formatMinorUnits(
            min($affiliate->availableBalanceBaisa(), (int) config('affiliate.minimum_payout_baisa', 20000)),
            'OMR',
        );
    }

    public function submit(CreateAffiliatePayoutRequest $createPayoutRequest): void
    {
        $affiliate = Auth::guard('affiliate')->user();

        abort_unless($affiliate instanceof Affiliate, 403);

        $validated = $this->validate([
            'amount' => ['required', 'regex:/^\d+(\.\d{1,3})?$/'],
            'affiliate_notes' => ['nullable', 'string', 'max:1000'],
            'payment_details' => ['required', 'string', 'max:2000'],
        ]);

        try {
            $amountBaisa = MoneyFactory::decimalStringToMinorUnits((string) $validated['amount'], 'OMR');
        } catch (\InvalidArgumentException|MathException) {
            throw ValidationException::withMessages([
                'amount' => __('validation.regex', ['attribute' => __('ui.payments.amount')]),
            ]);
        }

        $createPayoutRequest->execute(
            $affiliate,
            $amountBaisa,
            $validated['affiliate_notes'] ?: null,
            ['details' => $validated['payment_details']],
        );

        Flux::toast(variant: 'success', text: __('ui.affiliates.payout_created'));

        $this->redirectRoute('affiliate.dashboard', navigate: true);
    }

    /**
     * @return array{affiliate: Affiliate, availableBalanceBaisa: int, minimumPayoutBaisa: int}
     */
    public function with(): array
    {
        $affiliate = Auth::guard('affiliate')->user();

        abort_unless($affiliate instanceof Affiliate, 403);

        return [
            'affiliate' => $affiliate,
            'availableBalanceBaisa' => $affiliate->availableBalanceBaisa(),
            'minimumPayoutBaisa' => (int) config('affiliate.minimum_payout_baisa', 20000),
        ];
    }

};
?>

<section class="mx-auto flex max-w-3xl flex-col gap-6">
    <div class="rounded-2xl bg-emerald-900 p-6 text-white shadow-sm md:p-8">
        <div class="flex items-start gap-4">
            <span class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-amber-300/15 text-amber-100 ring-1 ring-amber-200/20">
                <x-hugeicon name="wallet-02" class="text-3xl" />
            </span>
            <div class="space-y-2">
                <flux:heading size="xl" class="text-white">{{ __('ui.affiliates.payout_request') }}</flux:heading>
                <flux:text class="text-emerald-50">{{ __('ui.affiliates.payout_request_description') }}</flux:text>
            </div>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-xl border border-emerald-900/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
            <flux:text>{{ __('ui.affiliates.available_balance') }}</flux:text>
            <flux:heading class="text-base"><x-money :amount-baisa="$availableBalanceBaisa" /></flux:heading>
        </div>
        <div class="rounded-xl border border-emerald-900/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
            <flux:text>{{ __('ui.affiliates.minimum_payout') }}</flux:text>
            <flux:heading class="text-base"><x-money :amount-baisa="$minimumPayoutBaisa" /></flux:heading>
        </div>
    </div>

    <form wire:submit="submit" class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
        <div class="space-y-5">
            <flux:input.group>
                <flux:input
                    wire:model="amount"
                    :label="__('ui.payments.amount')"
                    required
                    dir="ltr"
                />
                <flux:input.group.suffix>
                    <x-money symbol-only class="text-zinc-500 dark:text-white/60" />
                </flux:input.group.suffix>
            </flux:input.group>

            <flux:textarea
                wire:model="payment_details"
                :label="__('ui.affiliates.payment_details')"
                required
                rows="4"
            />

            <flux:textarea
                wire:model="affiliate_notes"
                :label="__('ui.affiliates.notes')"
                rows="3"
            />

            <div class="flex flex-wrap gap-3">
                <flux:button type="submit" variant="primary" :disabled="$availableBalanceBaisa < $minimumPayoutBaisa">
                    <x-hugeicon name="check-list" class="text-lg" />
                    {{ __('ui.actions.submit_request') }}
                </flux:button>
                <flux:button :href="route('affiliate.dashboard')" wire:navigate>
                    {{ __('ui.actions.cancel') }}
                </flux:button>
            </div>
        </div>
    </form>
</section>
