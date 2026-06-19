<?php

namespace App\Actions;

use App\Enums\LedgerAccountType;
use App\Enums\PaymentState;
use App\Models\LedgerAccount;
use App\Models\LedgerTransaction;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PostPaymentLedgerTransaction
{
    public function execute(Payment $payment): LedgerTransaction
    {
        return DB::transaction(function () use ($payment): LedgerTransaction {
            $payment = Payment::query()
                ->whereKey($payment->id)
                ->with('bookingInstallment.paymentSchedule.booking')
                ->lockForUpdate()
                ->firstOrFail();

            if ($payment->state !== PaymentState::Paid) {
                throw new RuntimeException('Only paid payments can be posted to the ledger.');
            }

            $existingTransaction = $payment->ledgerTransaction()->with('entries')->first();

            if ($existingTransaction) {
                return $existingTransaction;
            }

            $thawaniClearingAccount = $this->account(
                LedgerAccount::THAWANI_CLEARING_CODE,
                'Thawani clearing',
                LedgerAccountType::Asset,
                $payment->currency,
            );

            $customerDepositsAccount = $this->account(
                LedgerAccount::CUSTOMER_DEPOSITS_CODE,
                'Customer deposits',
                LedgerAccountType::Liability,
                $payment->currency,
            );

            $transaction = $payment->ledgerTransaction()->create([
                'reference' => 'LED-'.$payment->reference,
                'description' => 'Thawani payment '.$payment->reference,
                'occurred_at' => $payment->paid_at ?? now(),
                'currency' => $payment->currency,
                'total_baisa' => $payment->amount_baisa,
            ]);

            $transaction->entries()->createMany([
                [
                    'ledger_account_id' => $thawaniClearingAccount->id,
                    'debit_baisa' => $payment->amount_baisa,
                    'credit_baisa' => 0,
                    'currency' => $payment->currency,
                    'memo' => $this->memo($payment),
                ],
                [
                    'ledger_account_id' => $customerDepositsAccount->id,
                    'debit_baisa' => 0,
                    'credit_baisa' => $payment->amount_baisa,
                    'currency' => $payment->currency,
                    'memo' => $this->memo($payment),
                ],
            ]);

            $transaction->load('entries');

            $debits = $transaction->entries->sum('debit_baisa');
            $credits = $transaction->entries->sum('credit_baisa');

            if ($debits !== $credits || $debits !== $payment->amount_baisa) {
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

    private function memo(Payment $payment): string
    {
        $payment->loadMissing('bookingInstallment.paymentSchedule.booking');

        return Str::limit('Booking '.$payment->bookingInstallment->paymentSchedule->booking->reference.' payment '.$payment->reference, 255, '');
    }
}
