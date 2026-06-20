<?php

use App\Enums\BookingInstallmentState;
use App\Enums\PaymentRefundState;
use App\Enums\PaymentState;
use App\Models\BookingPaymentSchedule;
use App\Support\Money\MoneyFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Payments')] class extends Component {
    /**
     * @return array{
     *     schedules: Collection<int, BookingPaymentSchedule>,
     *     totalBaisa: int,
     *     paidBaisa: int,
     *     refundedBaisa: int,
     *     outstandingBaisa: int,
     *     currency: string
     * }
     */
    public function with(): array
    {
        $schedules = BookingPaymentSchedule::query()
            ->whereHas('booking', fn ($query) => $query->where('customer_id', Auth::guard('customer')->id()))
            ->with([
                'booking.event',
                'installments.payments' => fn ($query) => $query->latest(),
                'installments.payments.refunds' => fn ($query) => $query->latest(),
            ])
            ->latest()
            ->get();

        $installments = $schedules->flatMap->installments;
        $payments = $installments->flatMap->payments;
        $successfulPayments = $payments->filter(fn ($payment): bool => in_array($payment->state, [
            PaymentState::Paid,
            PaymentState::PartiallyRefunded,
            PaymentState::Refunded,
        ], true));
        $refunds = $payments
            ->flatMap->refunds
            ->filter(fn ($refund): bool => $refund->state === PaymentRefundState::Succeeded);
        $totalBaisa = (int) $installments->sum('amount_baisa');
        $paidBaisa = (int) $successfulPayments->sum('amount_baisa');
        $refundedBaisa = (int) $refunds->sum('amount_baisa');
        $currency = $schedules->first()?->currency ?? 'OMR';
        $total = MoneyFactory::fromMinor($totalBaisa, $currency);
        $paid = MoneyFactory::fromMinor($paidBaisa, $currency);
        $refunded = MoneyFactory::fromMinor($refundedBaisa, $currency);
        $netPaid = $paid->minus($refunded);

        if ($netPaid->isNegative()) {
            $netPaid = MoneyFactory::zero($currency);
        }

        $outstanding = $total->minus($netPaid);

        if ($outstanding->isNegative()) {
            $outstanding = MoneyFactory::zero($currency);
        }

        return [
            'schedules' => $schedules,
            'totalBaisa' => $totalBaisa,
            'paidBaisa' => $paidBaisa,
            'refundedBaisa' => $refundedBaisa,
            'outstandingBaisa' => MoneyFactory::toMinor($outstanding),
            'currency' => $currency,
        ];
    }

}; ?>

<section class="flex flex-col gap-6">
    <div class="overflow-hidden rounded-2xl bg-emerald-900 text-white shadow-sm">
        <div class="flex flex-col justify-between gap-6 p-6 md:flex-row md:items-end md:p-8">
            <div class="flex items-start gap-4">
                <span class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-amber-300/15 text-amber-100 ring-1 ring-amber-200/20">
                    <x-hugeicon name="wallet-02" class="text-3xl" />
                </span>
                <div>
                    <flux:heading size="xl" class="text-white">{{ __('ui.payments.heading') }}</flux:heading>
                    <flux:subheading class="text-emerald-50">{{ __('ui.payments.list_subheading') }}</flux:subheading>
                </div>
            </div>

            <flux:button :href="route('bookings.index')" wire:navigate variant="ghost" class="text-emerald-50 hover:bg-white/10 hover:text-white">
                <x-hugeicon name="contracts" class="text-lg" />
                {{ __('ui.actions.view_bookings') }}
            </flux:button>
        </div>
    </div>

    <div class="grid gap-3 md:grid-cols-4">
        <div class="rounded-xl border border-emerald-900/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-200">
                    <x-hugeicon name="invoice-03" class="text-xl" />
                </span>
                <div>
                    <flux:text>{{ __('ui.payments.total_scheduled') }}</flux:text>
                    <flux:heading class="text-base"><x-money :amount-baisa="$totalBaisa" :currency="$currency" /></flux:heading>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-emerald-900/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl bg-green-100 text-green-800 dark:bg-green-400/15 dark:text-green-200">
                    <x-hugeicon name="checkmark-badge-01" class="text-xl" />
                </span>
                <div>
                    <flux:text>{{ __('ui.payments.total_paid') }}</flux:text>
                    <flux:heading class="text-base"><x-money :amount-baisa="$paidBaisa" :currency="$currency" /></flux:heading>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-emerald-900/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl bg-amber-100 text-amber-800 dark:bg-amber-300/15 dark:text-amber-100">
                    <x-hugeicon name="refund-02" class="text-xl" />
                </span>
                <div>
                    <flux:text>{{ __('ui.payments.total_refunded') }}</flux:text>
                    <flux:heading class="text-base"><x-money :amount-baisa="$refundedBaisa" :currency="$currency" /></flux:heading>
                </div>
            </div>
        </div>
        <div class="rounded-xl border border-emerald-900/10 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center gap-3">
                <span class="flex size-10 items-center justify-center rounded-xl bg-sky-100 text-sky-800 dark:bg-sky-300/15 dark:text-sky-100">
                    <x-hugeicon name="clock-01" class="text-xl" />
                </span>
                <div>
                    <flux:text>{{ __('ui.payments.outstanding') }}</flux:text>
                    <flux:heading class="text-base"><x-money :amount-baisa="$outstandingBaisa" :currency="$currency" /></flux:heading>
                </div>
            </div>
        </div>
    </div>

    @if ($schedules->isNotEmpty())
        <div class="space-y-5">
            @foreach ($schedules as $schedule)
                <div wire:key="payment-schedule-{{ $schedule->id }}" class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-white/5">
                    @php
                        $schedulePaidBaisa = (int) $schedule->installments
                            ->where('state', BookingInstallmentState::Paid)
                            ->sum('amount_baisa');
                        $scheduleProgress = $schedule->total_baisa > 0
                            ? min(100, (int) round(($schedulePaidBaisa / $schedule->total_baisa) * 100))
                            : 0;
                    @endphp

                    <div class="mb-5 flex flex-col justify-between gap-4 md:flex-row md:items-start">
                        <div class="flex items-start gap-4">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-200">
                                <x-hugeicon name="payment-02" class="text-2xl" />
                            </span>
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <flux:badge color="emerald">{{ $schedule->booking->reference }}</flux:badge>
                                    <flux:badge color="amber">{{ $schedule->plan_name }}</flux:badge>
                                </div>
                                <flux:heading>{{ $schedule->booking->event->name }}</flux:heading>
                                <flux:text>{{ __('ui.payments.schedule_total') }} <x-money :amount-baisa="$schedule->total_baisa" :currency="$schedule->currency" /></flux:text>
                                <div class="h-2 w-full max-w-sm overflow-hidden rounded-full bg-emerald-100 dark:bg-white/10">
                                    <div class="h-full rounded-full bg-emerald-600 dark:bg-emerald-300" style="width: {{ $scheduleProgress }}%"></div>
                                </div>
                            </div>
                        </div>

                        <flux:button :href="route('bookings.show', $schedule->booking)" wire:navigate size="sm">
                            <x-hugeicon name="arrow-left-02" class="text-base" />
                            {{ __('ui.actions.open') }}
                        </flux:button>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-emerald-900/10 dark:border-white/10">
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>{{ __('ui.payments.installment') }}</flux:table.column>
                                <flux:table.column>{{ __('ui.payments.due_date') }}</flux:table.column>
                                <flux:table.column>{{ __('ui.payments.amount') }}</flux:table.column>
                                <flux:table.column>{{ __('ui.labels.status') }}</flux:table.column>
                                <flux:table.column>{{ __('ui.payments.payment_attempts') }}</flux:table.column>
                            </flux:table.columns>

                            <flux:table.rows>
                                @foreach ($schedule->installments as $installment)
                                    <flux:table.row wire:key="payments-installment-{{ $installment->id }}">
                                        <flux:table.cell variant="strong">
                                            {{ $installment->name ?: __('ui.payments.installment_number', ['number' => $installment->sequence]) }}
                                        </flux:table.cell>
                                        <flux:table.cell dir="ltr">{{ $installment->due_date->format('Y-m-d') }}</flux:table.cell>
                                        <flux:table.cell><x-money :amount-baisa="$installment->amount_baisa" :currency="$schedule->currency" /></flux:table.cell>
                                        <flux:table.cell>
                                            <x-status-badge :state="$installment->state" />
                                        </flux:table.cell>
                                        <flux:table.cell>
                                            @if ($installment->payments->isNotEmpty())
                                                <div class="space-y-3">
                                                    @foreach ($installment->payments as $payment)
                                                        <div wire:key="customer-payment-{{ $payment->id }}" class="rounded-lg border border-emerald-900/10 bg-emerald-50/40 p-3 dark:border-white/10 dark:bg-white/5">
                                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                                <div class="space-y-1">
                                                                    <div class="flex flex-wrap items-center gap-2">
                                                                        <span class="font-medium">{{ $payment->reference }}</span>
                                                                        <x-status-badge :state="$payment->state" />
                                                                    </div>
                                                                    <div class="text-sm text-zinc-600 dark:text-white/60" dir="ltr">
                                                                        {{ $payment->paid_at?->format('Y-m-d H:i') ?? $payment->created_at->format('Y-m-d H:i') }}
                                                                    </div>
                                                                </div>
                                                                <x-money :amount-baisa="$payment->amount_baisa" :currency="$payment->currency" class="font-medium" />
                                                            </div>

                                                            @if ($payment->refunds->isNotEmpty())
                                                                <div class="mt-3 space-y-2 border-t border-emerald-900/10 pt-3 dark:border-white/10">
                                                                    @foreach ($payment->refunds as $refund)
                                                                        <div wire:key="customer-refund-{{ $refund->id }}" class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                                                            <div class="flex flex-wrap items-center gap-2">
                                                                                <span>{{ __('ui.payments.refund') }} {{ $refund->reference }}</span>
                                                                                <x-status-badge :state="$refund->state" size="sm" />
                                                                            </div>
                                                                            <x-money :amount-baisa="$refund->amount_baisa" :currency="$refund->currency" />
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-sm text-zinc-500 dark:text-white/60">{{ __('ui.payments.no_payment_attempts') }}</span>
                                            @endif
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="rounded-2xl border border-dashed border-emerald-900/20 bg-white p-8 text-center shadow-sm dark:border-white/15 dark:bg-white/5">
            <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-xl bg-emerald-100 text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-200">
                <x-hugeicon name="wallet-02" class="text-2xl" />
            </div>
            <flux:text>{{ __('ui.payments.empty') }}</flux:text>
        </div>
    @endif
</section>
