<?php

use App\Models\Affiliate;
use App\Support\MoneyFormatter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::affiliate')] #[Title('Affiliate dashboard')] class extends Component
{
    /**
     * @return array{
     *     affiliate: Affiliate,
     *     affiliateLink: string,
     *     earnedCommissionBaisa: int,
     *     availableBalanceBaisa: int,
     *     pendingPayoutBaisa: int,
     *     paidPayoutBaisa: int,
     *     referralsCount: int,
     *     paidReferralsCount: int,
     *     recentCommissions: Collection<int, mixed>,
     *     recentPayouts: Collection<int, mixed>
     * }
     */
    public function with(): array
    {
        $affiliate = Auth::guard('affiliate')->user();

        abort_unless($affiliate instanceof Affiliate, 403);

        return [
            'affiliate' => $affiliate,
            'affiliateLink' => $affiliate->affiliateLink(),
            'earnedCommissionBaisa' => $affiliate->earnedCommissionBaisa(),
            'availableBalanceBaisa' => $affiliate->availableBalanceBaisa(),
            'pendingPayoutBaisa' => $affiliate->pendingPayoutBaisa(),
            'paidPayoutBaisa' => $affiliate->paidPayoutBaisa(),
            'referralsCount' => $affiliate->referrals()->count(),
            'paidReferralsCount' => $affiliate->paidReferredBookingsCount(),
            'recentCommissions' => $affiliate->commissions()
                ->with(['booking.event', 'payment'])
                ->latest('earned_at')
                ->limit(5)
                ->get(),
            'recentPayouts' => $affiliate->payoutRequests()
                ->latest()
                ->limit(5)
                ->get(),
        ];
    }

    public function money(int $amountBaisa, string $currency = 'OMR'): string
    {
        return MoneyFormatter::baisa($amountBaisa, $currency);
    }
};
?>

<section class="flex flex-col gap-6">
    <div class="overflow-hidden rounded-2xl bg-emerald-900 text-white shadow-sm">
        <div class="flex flex-col justify-between gap-6 p-6 md:flex-row md:items-end md:p-8">
            <div class="flex items-start gap-4">
                <span class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-amber-300/15 text-amber-100 ring-1 ring-amber-200/20">
                    <x-hugeicon name="ticket-01" class="text-3xl" />
                </span>
                <div class="space-y-2">
                    <flux:heading size="xl" class="text-white">{{ __('ui.affiliates.dashboard_title') }}</flux:heading>
                    <flux:subheading class="text-emerald-50">{{ __('ui.affiliates.dashboard_description') }}</flux:subheading>
                </div>
            </div>

            <flux:button :href="route('affiliate.payouts.request')" wire:navigate variant="ghost" class="text-emerald-50 hover:bg-white/10 hover:text-white">
                <x-hugeicon name="wallet-02" class="text-lg" />
                {{ __('ui.actions.request_payout') }}
            </flux:button>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-[1.1fr_.9fr]">
        <div class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5" x-data="{ copied: false, link: @js($affiliateLink) }">
            <div class="mb-4 flex flex-col gap-2">
                <flux:heading>{{ __('ui.affiliates.referral_link') }}</flux:heading>
                <flux:text>{{ __('ui.affiliates.referral_link_description') }}</flux:text>
            </div>

            <div class="grid gap-3 md:grid-cols-[1fr_auto]">
                <flux:input value="{{ $affiliateLink }}" readonly dir="ltr" />
                <flux:button type="button" x-on:click="navigator.clipboard.writeText(link); copied = true; setTimeout(() => copied = false, 1800)">
                    <x-hugeicon name="copy-01" class="text-lg" />
                    <span x-text="copied ? @js(__('ui.affiliates.copied')) : @js(__('ui.actions.copy_link'))"></span>
                </flux:button>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                <flux:badge color="emerald">{{ __('ui.affiliates.code') }}: {{ $affiliate->code }}</flux:badge>
                <flux:badge color="amber">{{ __('ui.affiliates.rate') }} {{ (int) config('affiliate.commission_rate_basis_points', 500) / 100 }}%</flux:badge>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl border border-emerald-900/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
                <flux:text>{{ __('ui.affiliates.earned_total') }}</flux:text>
                <flux:heading class="text-base">{{ $this->money($earnedCommissionBaisa) }}</flux:heading>
            </div>
            <div class="rounded-xl border border-emerald-900/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
                <flux:text>{{ __('ui.affiliates.available_balance') }}</flux:text>
                <flux:heading class="text-base">{{ $this->money($availableBalanceBaisa) }}</flux:heading>
            </div>
            <div class="rounded-xl border border-emerald-900/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
                <flux:text>{{ __('ui.affiliates.referrals') }}</flux:text>
                <flux:heading class="text-base">{{ $referralsCount }}</flux:heading>
            </div>
            <div class="rounded-xl border border-emerald-900/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
                <flux:text>{{ __('ui.affiliates.paid_referrals') }}</flux:text>
                <flux:heading class="text-base">{{ $paidReferralsCount }}</flux:heading>
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="mb-4 flex items-center justify-between gap-3">
                <flux:heading>{{ __('ui.affiliates.recent_commissions') }}</flux:heading>
                <flux:badge color="emerald">{{ $this->money($earnedCommissionBaisa) }}</flux:badge>
            </div>

            @if ($recentCommissions->isNotEmpty())
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('ui.labels.event') }}</flux:table.column>
                        <flux:table.column>{{ __('ui.payments.payment') }}</flux:table.column>
                        <flux:table.column>{{ __('ui.payments.amount') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($recentCommissions as $commission)
                            <flux:table.row wire:key="affiliate-commission-{{ $commission->id }}">
                                <flux:table.cell>{{ $commission->booking->event->name ?? $commission->booking->reference }}</flux:table.cell>
                                <flux:table.cell dir="ltr">{{ $commission->payment->reference }}</flux:table.cell>
                                <flux:table.cell>{{ $this->money($commission->commission_amount_baisa, $commission->currency) }}</flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @else
                <flux:text>{{ __('ui.affiliates.no_commissions') }}</flux:text>
            @endif
        </div>

        <div class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <flux:heading>{{ __('ui.affiliates.payouts') }}</flux:heading>
                <div class="flex flex-wrap gap-2">
                    <flux:badge color="amber">{{ __('ui.affiliates.pending') }} {{ $this->money($pendingPayoutBaisa) }}</flux:badge>
                    <flux:badge color="emerald">{{ __('ui.affiliates.paid') }} {{ $this->money($paidPayoutBaisa) }}</flux:badge>
                </div>
            </div>

            @if ($recentPayouts->isNotEmpty())
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('ui.fields.name') }}</flux:table.column>
                        <flux:table.column>{{ __('ui.payments.amount') }}</flux:table.column>
                        <flux:table.column>{{ __('ui.labels.status') }}</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($recentPayouts as $payout)
                            <flux:table.row wire:key="affiliate-payout-{{ $payout->id }}">
                                <flux:table.cell dir="ltr">{{ $payout->reference }}</flux:table.cell>
                                <flux:table.cell>{{ $this->money($payout->amount_baisa, $payout->currency) }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:badge>{{ $payout->status->getLabel() }}</flux:badge>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            @else
                <flux:text>{{ __('ui.affiliates.no_payouts') }}</flux:text>
            @endif
        </div>
    </div>
</section>
