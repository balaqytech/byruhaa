<?php

namespace App\Actions;

use App\Enums\LedgerAccountType;
use App\Enums\PaymentRefundState;
use App\Models\LedgerAccount;
use App\Models\LedgerTransaction;
use App\Models\PaymentRefund;
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
                LedgerAccount::CUSTOMER_DEPOSITS_CODE,
                'Customer deposits',
                LedgerAccountType::Liability,
                $paymentRefund->currency,
            );

            $transaction = $paymentRefund->ledgerTransaction()->create([
                'reference' => 'LED-'.$paymentRefund->reference,
                'description' => 'Thawani refund '.$paymentRefund->reference,
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
                'name' => $name,
                'type' => $type,
                'currency' => $currency,
                'is_active' => true,
            ],
        );
    }

    private function memo(PaymentRefund $paymentRefund): string
    {
        $paymentRefund->loadMissing('payment.bookingInstallment.paymentSchedule.booking');

        return Str::limit('Booking '.$paymentRefund->payment->bookingInstallment->paymentSchedule->booking->reference.' refund '.$paymentRefund->reference, 255, '');
    }
}
