<?php

use App\Models\Affiliate;
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
     *     commissionAmountBaisa: int,
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
            'commissionAmountBaisa' => (int) config('affiliate.commission_amount_baisa', 30000),
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

};
?>

<section class="flex flex-col gap-5">
    <div class="overflow-hidden rounded-2xl border border-emerald-800/20 bg-emerald-950 text-white shadow-sm shadow-emerald-950/10 dark:border-emerald-300/10">
        <div class="grid gap-6 p-5 sm:p-6 lg:grid-cols-[1fr_auto] lg:items-center lg:p-7">
            <div class="flex min-w-0 items-start gap-4">
                <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-white/10 text-emerald-50 ring-1 ring-white/15">
                    <x-hugeicon name="ticket-01" class="text-2xl" />
                </span>

                <div class="min-w-0 space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:heading size="xl" class="text-white">{{ __('ui.affiliates.dashboard_title') }}</flux:heading>
                        <flux:badge color="emerald">{{ __('ui.affiliates.code') }}: {{ $affiliate->code }}</flux:badge>
                    </div>
                    <flux:subheading class="max-w-2xl text-emerald-50">{{ __('ui.affiliates.dashboard_description') }}</flux:subheading>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-[auto_auto] lg:min-w-96">
                <div class="rounded-xl border border-white/10 bg-white/10 p-4">
                    <p class="text-xs font-medium uppercase text-emerald-100">{{ __('ui.affiliates.fixed_commission') }}</p>
                    <p class="mt-1 text-2xl font-semibold text-white"><x-money :amount-baisa="$commissionAmountBaisa" /></p>
                    <p class="mt-1 text-sm text-emerald-100">{{ __('ui.affiliates.per_booking') }}</p>
                </div>

                <flux:button :href="route('affiliate.payouts.request')" wire:navigate variant="primary" class="min-h-20 justify-center bg-white text-emerald-950 hover:bg-emerald-50">
                    <x-hugeicon name="wallet-02" class="text-lg" />
                    {{ __('ui.actions.request_payout') }}
                </flux:button>
            </div>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-emerald-900/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center justify-between gap-3">
                <flux:text>{{ __('ui.affiliates.earned_total') }}</flux:text>
                <span class="flex size-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-200">
                    <x-hugeicon name="invoice-03" class="text-lg" />
                </span>
            </div>
            <flux:heading class="mt-3 text-lg"><x-money :amount-baisa="$earnedCommissionBaisa" /></flux:heading>
        </div>

        <div class="rounded-xl border border-emerald-900/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center justify-between gap-3">
                <flux:text>{{ __('ui.affiliates.available_balance') }}</flux:text>
                <span class="flex size-9 items-center justify-center rounded-lg bg-sky-50 text-sky-700 dark:bg-sky-400/10 dark:text-sky-200">
                    <x-hugeicon name="wallet-02" class="text-lg" />
                </span>
            </div>
            <flux:heading class="mt-3 text-lg"><x-money :amount-baisa="$availableBalanceBaisa" /></flux:heading>
        </div>

        <div class="rounded-xl border border-emerald-900/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center justify-between gap-3">
                <flux:text>{{ __('ui.affiliates.referrals') }}</flux:text>
                <span class="flex size-9 items-center justify-center rounded-lg bg-violet-50 text-violet-700 dark:bg-violet-400/10 dark:text-violet-200">
                    <x-hugeicon name="user-group" class="text-lg" />
                </span>
            </div>
            <flux:heading class="mt-3 text-lg">{{ $referralsCount }}</flux:heading>
        </div>

        <div class="rounded-xl border border-emerald-900/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center justify-between gap-3">
                <flux:text>{{ __('ui.affiliates.paid_referrals') }}</flux:text>
                <span class="flex size-9 items-center justify-center rounded-lg bg-amber-50 text-amber-700 dark:bg-amber-400/10 dark:text-amber-200">
                    <x-hugeicon name="checkmark-badge-01" class="text-lg" />
                </span>
            </div>
            <flux:heading class="mt-3 text-lg">{{ $paidReferralsCount }}</flux:heading>
        </div>
    </div>

    <div class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5" x-data="{ copied: false, link: @js($affiliateLink) }">
        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
            <div class="min-w-0">
                <div class="mb-4 flex flex-col gap-1">
                    <flux:heading>{{ __('ui.affiliates.referral_link') }}</flux:heading>
                    <flux:text>{{ __('ui.affiliates.referral_link_description') }}</flux:text>
                </div>

                <div class="grid gap-3 md:grid-cols-[1fr_auto]">
                    <flux:input value="{{ $affiliateLink }}" readonly dir="ltr" />
                    <flux:button type="button" variant="primary" x-on:click="navigator.clipboard.writeText(link); copied = true; setTimeout(() => copied = false, 1800)">
                        <x-hugeicon name="copy-01" class="text-lg" />
                        <span x-text="copied ? @js(__('ui.affiliates.copied')) : @js(__('ui.actions.copy_link'))"></span>
                    </flux:button>
                </div>
            </div>

            <div class="grid gap-2 sm:grid-cols-2 lg:w-80">
                <div class="rounded-xl bg-emerald-50 p-4 dark:bg-emerald-400/10">
                    <flux:text>{{ __('ui.affiliates.code') }}</flux:text>
                    <p class="mt-1 font-mono text-base font-semibold text-emerald-950 dark:text-emerald-50" dir="ltr">{{ $affiliate->code }}</p>
                </div>
                <div class="rounded-xl bg-zinc-50 p-4 dark:bg-white/10">
                    <flux:text>{{ __('ui.affiliates.fixed_commission') }}</flux:text>
                    <p class="mt-1 text-base font-semibold text-zinc-950 dark:text-white"><x-money :amount-baisa="$commissionAmountBaisa" /></p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-2">
        <div class="rounded-2xl border border-emerald-900/10 bg-white shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center justify-between gap-3 border-b border-emerald-900/10 p-5 dark:border-white/10">
                <div>
                    <flux:heading>{{ __('ui.affiliates.recent_commissions') }}</flux:heading>
                    <flux:text class="inline-flex items-center gap-1">{{ __('ui.affiliates.fixed_commission') }} <x-money :amount-baisa="$commissionAmountBaisa" /></flux:text>
                </div>
                <flux:badge color="emerald"><x-money :amount-baisa="$earnedCommissionBaisa" /></flux:badge>
            </div>

            <div class="p-2">
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
                                    <flux:table.cell><x-money :amount-baisa="$commission->commission_amount_baisa" :currency="$commission->currency" /></flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @else
                    <div class="p-4">
                        <flux:text>{{ __('ui.affiliates.no_commissions') }}</flux:text>
                    </div>
                @endif
            </div>
        </div>

        <div class="rounded-2xl border border-emerald-900/10 bg-white shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-emerald-900/10 p-5 dark:border-white/10">
                <flux:heading>{{ __('ui.affiliates.payouts') }}</flux:heading>
                <div class="flex flex-wrap gap-2">
                    <flux:badge color="amber"><span class="inline-flex items-center gap-1">{{ __('ui.affiliates.pending') }} <x-money :amount-baisa="$pendingPayoutBaisa" /></span></flux:badge>
                    <flux:badge color="emerald"><span class="inline-flex items-center gap-1">{{ __('ui.affiliates.paid') }} <x-money :amount-baisa="$paidPayoutBaisa" /></span></flux:badge>
                </div>
            </div>

            <div class="p-2">
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
                                    <flux:table.cell><x-money :amount-baisa="$payout->amount_baisa" :currency="$payout->currency" /></flux:table.cell>
                                    <flux:table.cell>
                                        <flux:badge>{{ $payout->status->getLabel() }}</flux:badge>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                @else
                    <div class="p-4">
                        <flux:text>{{ __('ui.affiliates.no_payouts') }}</flux:text>
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
