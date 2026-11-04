<?php

namespace App\Modules\Finance\Actions;

use App\Modules\Finance\Models\CashClosure;
use App\Modules\Finance\Models\FinancialEvent;
use App\Modules\Finance\Models\WalletWithdrawalRequest;
use App\Modules\Identity\Models\User;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\OrderRefundRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CloseCashDrawer
{
    public function execute(int $cashierId, string $businessDate, int $openingFloatBaisa, int $countedBaisa, int $actorUserId, ?string $notes = null): CashClosure
    {
        if ($openingFloatBaisa < 0 || $countedBaisa < 0) {
            throw ValidationException::withMessages(['cash' => 'Cash amounts cannot be negative.']);
        }

        return DB::transaction(function () use ($cashierId, $businessDate, $openingFloatBaisa, $countedBaisa, $actorUserId, $notes): CashClosure {
            User::query()->findOrFail($cashierId);
            $actor = User::query()->findOrFail($actorUserId);
            if (! $actor->isPanelAdministrator() && ! $actor->can('Create:CashClosure')) {
                throw ValidationException::withMessages(['cash' => 'This employee is not allowed to close cash drawers.']);
            }
            if (CashClosure::query()->where('cashier_user_id', $cashierId)->whereDate('business_date', $businessDate)->exists()) {
                throw ValidationException::withMessages(['cash' => 'This cashier already has a closure for the date.']);
            }

            $start = Carbon::parse($businessDate, config('app.timezone'))->startOfDay()->utc();
            $end = Carbon::parse($businessDate, config('app.timezone'))->endOfDay()->utc();
            $orders = Order::query()->where('payment_method', 'cash')->where('pos_cashier_user_id', $cashierId)->whereBetween('paid_at', [$start, $end])->get();
            $refunds = OrderRefundRequest::query()->where('status', 'paid')->where('paid_by_user_id', $cashierId)->whereBetween('paid_at', [$start, $end])->whereHas('order', fn ($query) => $query->where('payment_method', 'cash'))->get();
            $withdrawals = WalletWithdrawalRequest::query()->where('status', 'paid')->where('method', 'cash')->where('paid_by_user_id', $cashierId)->whereBetween('paid_at', [$start, $end])->get();

            $issues = [];
            foreach ($orders as $order) {
                if (! FinancialEvent::query()->where('event_key', 'store-payment:'.$order->id)->exists()) {
                    $issues[] = 'Missing Ledger: order '.$order->reference;
                }
            }
            foreach ($refunds as $refund) {
                if (! FinancialEvent::query()->where('event_key', 'store-refund:'.$refund->id)->exists()) {
                    $issues[] = 'Missing Ledger: refund '.$refund->reference;
                }
            }
            foreach ($withdrawals as $withdrawal) {
                if (! FinancialEvent::query()->where('event_key', 'wallet-withdrawal:'.$withdrawal->id)->exists()) {
                    $issues[] = 'Missing Ledger: withdrawal '.$withdrawal->reference;
                }
            }

            $receiptsBaisa = (int) $orders->sum('total_baisa');
            $refundsBaisa = (int) $refunds->sum('amount_baisa');
            $withdrawalsBaisa = (int) $withdrawals->sum('amount_baisa');
            $expectedBaisa = $openingFloatBaisa + $receiptsBaisa - $refundsBaisa - $withdrawalsBaisa;
            $differenceBaisa = $countedBaisa - $expectedBaisa;

            return CashClosure::query()->create([
                'cashier_user_id' => $cashierId,
                'business_date' => $businessDate,
                'opening_float_baisa' => $openingFloatBaisa,
                'receipts_baisa' => $receiptsBaisa,
                'refunds_baisa' => $refundsBaisa,
                'withdrawals_baisa' => $withdrawalsBaisa,
                'expected_baisa' => $expectedBaisa,
                'counted_baisa' => $countedBaisa,
                'difference_baisa' => $differenceBaisa,
                'status' => $differenceBaisa === 0 && $issues === [] ? 'balanced' : 'discrepancy',
                'issues' => $issues,
                'closed_by_user_id' => $actorUserId,
                'notes' => $notes,
            ]);
        });
    }
}
