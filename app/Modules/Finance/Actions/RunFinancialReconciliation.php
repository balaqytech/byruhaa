<?php

namespace App\Modules\Finance\Actions;

use App\Enums\PaymentRefundState;
use App\Enums\PaymentState;
use App\Modules\Finance\Models\CashClosure;
use App\Modules\Finance\Models\FinancialReconciliationRun;
use App\Modules\Finance\Models\LedgerAccount;
use App\Modules\Finance\Models\LedgerEntry;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\PaymentRefund;
use App\Modules\Finance\Models\ThawaniSettlement;
use App\Modules\Finance\Models\ThawaniSettlementItem;
use App\Modules\Finance\Models\Wallet;
use App\Modules\Finance\Models\WalletWithdrawalRequest;
use App\Modules\Identity\Models\User;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\OrderRefundRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class RunFinancialReconciliation
{
    public function execute(string $asOfDate, int $actorUserId, ?string $bankStatementPath = null): FinancialReconciliationRun
    {
        $actor = User::query()->findOrFail($actorUserId);
        if (! $actor->isPanelAdministrator() && ! $actor->can('Create:FinancialReconciliationRun')) {
            throw ValidationException::withMessages(['reconciliation' => 'This employee cannot run financial reconciliation.']);
        }
        $issues = [];
        $addIssue = static function (string $source, string $reference, string $severity, string $message, string $owner) use (&$issues): void {
            $issues[] = compact('source', 'reference', 'severity', 'message', 'owner');
        };

        foreach (Wallet::query()->with('movements')->get() as $wallet) {
            $movementBalance = (int) $wallet->movements->sum(fn ($movement): int => $movement->credit_baisa - $movement->debit_baisa);
            if ($wallet->balance_baisa !== $movementBalance) {
                $addIssue('Wallet', (string) $wallet->id, 'discrepancy', 'Wallet balance differs from movements by '.($wallet->balance_baisa - $movementBalance).' baisa.', 'Finance');
            }
        }
        $walletLiability = (int) LedgerEntry::query()->whereHas('ledgerAccount', fn ($query) => $query->where('code', LedgerAccount::WALLET_LIABILITY_CODE))->selectRaw('COALESCE(SUM(credit_baisa - debit_baisa), 0) as balance')->value('balance');
        $walletBalances = (int) Wallet::query()->sum('balance_baisa');
        if ($walletLiability !== $walletBalances) {
            $addIssue('Wallet Ledger', LedgerAccount::WALLET_LIABILITY_CODE, 'discrepancy', 'Ledger liability '.$walletLiability.' differs from Wallet balances '.$walletBalances.' baisa.', 'Accountant');
        }

        foreach (CashClosure::query()->whereDate('business_date', '<=', $asOfDate)->where('status', 'discrepancy')->get() as $closure) {
            $addIssue('Cash', (string) $closure->id, 'discrepancy', 'Cashier '.$closure->cashier_user_id.' has difference '.$closure->difference_baisa.' baisa. '.implode(' ', $closure->issues ?? []), 'Cashier / Finance');
        }
        foreach (CashClosure::query()->whereDate('business_date', '<=', $asOfDate)->get() as $closure) {
            $day = $closure->business_date->toDateString();
            $receipts = (int) Order::query()->where('payment_method', 'cash')->where('pos_cashier_user_id', $closure->cashier_user_id)->whereDate('paid_at', $day)->sum('total_baisa');
            $refunds = (int) OrderRefundRequest::query()->where('status', 'paid')->where('paid_by_user_id', $closure->cashier_user_id)->whereDate('paid_at', $day)->whereHas('order', fn ($query) => $query->where('payment_method', 'cash'))->sum('amount_baisa');
            $withdrawals = (int) WalletWithdrawalRequest::query()->where('status', 'paid')->where('method', 'cash')->where('paid_by_user_id', $closure->cashier_user_id)->whereDate('paid_at', $day)->sum('amount_baisa');
            if ($receipts !== $closure->receipts_baisa || $refunds !== $closure->refunds_baisa || $withdrawals !== $closure->withdrawals_baisa) {
                $addIssue('Cash', (string) $closure->id, 'discrepancy', 'Financial activity changed after this cashier closure; recount and investigate.', 'Cashier / Finance');
            }
        }
        foreach (Order::query()->where('payment_method', 'cash')->whereNotNull('paid_at')->whereDate('paid_at', '<=', $asOfDate)->selectRaw('pos_cashier_user_id, DATE(paid_at) as business_day')->distinct()->get() as $cashDay) {
            if ($cashDay->pos_cashier_user_id === null || ! CashClosure::query()->where('cashier_user_id', $cashDay->pos_cashier_user_id)->whereDate('business_date', $cashDay->business_day)->exists()) {
                $addIssue('Cash', ($cashDay->business_day ?? $asOfDate).'/'.($cashDay->pos_cashier_user_id ?? 'unknown'), 'pending', 'Paid Cash POS activity has no daily cashier closure.', 'Cashier / Finance');
            }
        }
        foreach (WalletWithdrawalRequest::query()->whereIn('status', ['pending', 'approved', 'paying', 'manual_review'])->get() as $withdrawal) {
            $addIssue('Withdrawal', $withdrawal->reference, 'pending', 'Payout status: '.$withdrawal->status, 'Finance');
        }
        foreach (OrderRefundRequest::query()->whereIn('status', ['approved', 'processing', 'manual_review'])->get() as $refund) {
            $addIssue('Store Refund', $refund->reference, 'pending', 'Refund status: '.$refund->status, 'Finance');
        }
        foreach (PaymentRefund::query()->where('state', PaymentRefundState::ManualRequired->value)->get() as $refund) {
            $addIssue('Thawani Refund', $refund->reference, 'pending', 'Provider result requires manual review.', 'Finance');
        }
        foreach (Payment::query()->where('provider', 'thawani')->whereIn('state', [PaymentState::Paid->value, PaymentState::PartiallyRefunded->value, PaymentState::Refunded->value])->whereDate('paid_at', '<=', $asOfDate)->get() as $payment) {
            if (blank($payment->provider_payment_id) || ! ThawaniSettlementItem::query()->where('kind', 'payment')->where('provider_operation_reference', $payment->provider_payment_id)->exists()) {
                $addIssue('Thawani clearing', $payment->reference, 'pending', 'Paid provider operation is not in a recorded settlement.', 'Accountant');
            }
        }
        foreach (PaymentRefund::query()->where('state', PaymentRefundState::Succeeded->value)->whereDate('processed_at', '<=', $asOfDate)->get() as $refund) {
            if (blank($refund->provider_refund_id) || ! ThawaniSettlementItem::query()->where('kind', 'refund')->where('provider_operation_reference', $refund->provider_refund_id)->exists()) {
                $addIssue('Thawani clearing', $refund->reference, 'pending', 'Succeeded refund is not in a recorded provider settlement.', 'Accountant');
            }
        }

        $bankExpected = [];
        foreach (WalletWithdrawalRequest::query()->where('status', 'paid')->where('method', 'bank')->whereDate('paid_at', '<=', $asOfDate)->get() as $withdrawal) {
            if (array_key_exists($withdrawal->payout_reference, $bankExpected)) {
                $addIssue('Bank', $withdrawal->payout_reference, 'discrepancy', 'Bank reference is repeated in expected movements.', 'Accountant');
            }
            $bankExpected[$withdrawal->payout_reference] = -$withdrawal->amount_baisa;
        }
        foreach (ThawaniSettlement::query()->whereDate('settled_at', '<=', $asOfDate)->get() as $settlement) {
            if (array_key_exists($settlement->provider_reference, $bankExpected)) {
                $addIssue('Bank', $settlement->provider_reference, 'discrepancy', 'Bank reference is repeated in expected movements.', 'Accountant');
            }
            $bankExpected[$settlement->provider_reference] = $settlement->bank_net_baisa;
        }
        if ($bankStatementPath !== null) {
            $bankActual = $this->statementRows($bankStatementPath);
            foreach ($bankExpected as $reference => $amountBaisa) {
                if (! array_key_exists($reference, $bankActual)) {
                    $addIssue('Bank', $reference, 'pending', 'Expected Bank movement is absent from the uploaded statement.', 'Accountant');
                } elseif ($bankActual[$reference] !== $amountBaisa) {
                    $addIssue('Bank', $reference, 'discrepancy', 'Statement amount '.$bankActual[$reference].' differs from Ledger amount '.$amountBaisa.' baisa.', 'Accountant');
                }
            }
            foreach (array_diff_key($bankActual, $bankExpected) as $reference => $amountBaisa) {
                $addIssue('Bank', $reference, 'discrepancy', 'Statement movement '.$amountBaisa.' baisa has no matched payout or settlement.', 'Accountant');
            }
        } elseif ($bankExpected !== []) {
            $addIssue('Bank', 'statement', 'pending', 'Bank statement is needed to verify '.count($bankExpected).' transfer(s).', 'Accountant');
        }

        $discrepancies = count(array_filter($issues, fn (array $issue): bool => $issue['severity'] === 'discrepancy'));
        $pending = count($issues) - $discrepancies;

        return FinancialReconciliationRun::query()->create([
            'as_of_date' => $asOfDate,
            'bank_statement_path' => $bankStatementPath,
            'status' => $discrepancies > 0 ? 'discrepancy' : ($pending > 0 ? 'pending' : 'balanced'),
            'discrepancy_count' => $discrepancies,
            'pending_count' => $pending,
            'issues' => $issues,
            'run_by_user_id' => $actorUserId,
        ]);
    }

    /** @return array<string, int> */
    private function statementRows(string $path): array
    {
        if (! Storage::disk('local')->exists($path)) {
            throw ValidationException::withMessages(['bank_statement' => 'Bank statement file was not found.']);
        }
        $rows = [];
        foreach (preg_split('/\R/', trim(Storage::disk('local')->get($path))) ?: [] as $line) {
            $columns = array_map('trim', str_getcsv($line));
            if ($columns === ['reference', 'amount_baisa']) {
                continue;
            }
            if (count($columns) !== 2 || $columns[0] === '' || ! preg_match('/^-?\d+$/', $columns[1]) || isset($rows[$columns[0]])) {
                throw ValidationException::withMessages(['bank_statement' => 'Bank statement must contain unique reference,amount_baisa CSV rows.']);
            }
            $rows[$columns[0]] = (int) $columns[1];
        }

        return $rows;
    }
}
