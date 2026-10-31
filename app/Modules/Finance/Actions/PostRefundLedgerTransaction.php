<?php

namespace App\Modules\Finance\Actions;

use App\Enums\LedgerAccountType;
use App\Enums\PaymentRefundState;
use App\Modules\Finance\Models\LedgerAccount;
use App\Modules\Finance\Models\LedgerTransaction;
use App\Modules\Finance\Models\PaymentRefund;
use App\Support\LedgerText;
use App\Support\Money\MoneyFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PostRefundLedgerTransaction
{
    public function execute(PaymentRefund $paymentRefund): LedgerTransaction
    {
        return DB::transaction(function () use ($paymentRefund): LedgerTransaction {
            $paymentRefund = PaymentRefund::query()
                ->whereKey($paymentRefund->id)
                ->with('payment.bookingInstallment.paymentSchedule.booking')
                ->lockForUpdate()
                ->firstOrFail();

            if ($paymentRefund->state !== PaymentRefundState::Succeeded) {
                throw new RuntimeException('Only succeeded refunds can be posted to the ledger.');
            }

            $existingTransaction = $paymentRefund->ledgerTransaction()->with('entries')->first();

            if ($existingTransaction) {
                return $existingTransaction;
            }

            $thawaniClearingAccount = $this->account(
                LedgerAccount::THAWANI_CLEARING_CODE,
                'Thawani clearing',
                LedgerAccountType::Asset,
                $paymentRefund->currency,
            );

            $customerDepositsAccount = $this->account(
                $paymentRefund->payment->subject_type === 'wallet_topup' ? LedgerAccount::WALLET_LIABILITY_CODE : LedgerAccount::CUSTOMER_DEPOSITS_CODE,
                $paymentRefund->payment->subject_type === 'wallet_topup' ? 'Wallet liability' : 'Customer deposits',
                LedgerAccountType::Liability,
                $paymentRefund->currency,
            );

            $transaction = $paymentRefund->ledgerTransaction()->create([
                'description' => LedgerText::description('Thawani refund '.$paymentRefund->reference),
                'occurred_at' => $paymentRefund->processed_at ?? now(),
                'currency' => $paymentRefund->currency,
                'total_baisa' => MoneyFactory::toMinor($paymentRefund->amount),
            ]);

            $transaction->entries()->createMany([
                [
                    'ledger_account_id' => $customerDepositsAccount->id,
                    'debit_baisa' => MoneyFactory::toMinor($paymentRefund->amount),
                    'credit_baisa' => 0,
                    'currency' => $paymentRefund->currency,
                    'memo' => $this->memo($paymentRefund),
                ],
                [
                    'ledger_account_id' => $thawaniClearingAccount->id,
                    'debit_baisa' => 0,
                    'credit_baisa' => MoneyFactory::toMinor($paymentRefund->amount),
                    'currency' => $paymentRefund->currency,
                    'memo' => $this->memo($paymentRefund),
                ],
            ]);

            $transaction->load('entries');

            $debits = MoneyFactory::sumMinor($transaction->entries, 'debit_baisa', $paymentRefund->currency);
            $credits = MoneyFactory::sumMinor($transaction->entries, 'credit_baisa', $paymentRefund->currency);

            if (! $debits->isEqualTo($credits) || ! $debits->isEqualTo($paymentRefund->amount)) {
                throw new RuntimeException('Ledger transaction is not balanced.');
            }

            return $transaction;
        });
    }

    private function account(string $code, string $name, LedgerAccountType $type, string $currency): LedgerAccount
    {
        return LedgerAccount::query()->firstOrCreate(
            ['code' => $code],
            [
                'name' => LedgerText::accountName($code, $name),
                'type' => $type,
                'currency' => $currency,
                'is_active' => true,
            ],
        );
    }

    private function memo(PaymentRefund $paymentRefund): string
    {
        if ($paymentRefund->payment->subject_type === 'wallet_topup') {
            return Str::limit(LedgerText::description('Wallet top-up '.$paymentRefund->payment->subject_reference.' refund '.$paymentRefund->reference), 255, '');
        }

        if ($paymentRefund->payment->subject_type === 'store_order') {
            throw new RuntimeException('Store refunds require Store ledger posting.');
        }

        $paymentRefund->loadMissing('payment.bookingInstallment.paymentSchedule.booking');

        return Str::limit(LedgerText::description('Booking '.$paymentRefund->payment->bookingInstallment->paymentSchedule->booking->reference.' refund '.$paymentRefund->reference), 255, '');
    }
}
