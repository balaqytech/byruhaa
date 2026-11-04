<?php

namespace App\Modules\Finance\Actions;

use App\Enums\LedgerAccountType;
use App\Modules\Finance\Models\FinancialEvent;
use App\Modules\Finance\Models\LedgerAccount;
use App\Modules\Finance\Models\LedgerTransaction;
use App\Support\LedgerText;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PostFinancialEvent
{
    /**
     * @param  array<int, array{code: string, name: string, type: LedgerAccountType, debit: int, credit: int}>  $lines
     */
    public function execute(string $key, string $kind, string $subjectType, int $subjectId, string $description, string $currency, DateTimeInterface $occurredAt, array $lines): LedgerTransaction
    {
        return DB::transaction(function () use ($key, $kind, $subjectType, $subjectId, $description, $currency, $occurredAt, $lines): LedgerTransaction {
            $debits = array_sum(array_column($lines, 'debit'));
            $credits = array_sum(array_column($lines, 'credit'));
            if ($debits < 1 || $debits !== $credits) {
                throw new RuntimeException('Financial event must contain balanced positive entries.');
            }

            $event = FinancialEvent::query()->firstOrCreate(
                ['event_key' => $key],
                ['kind' => $kind, 'subject_type' => $subjectType, 'subject_id' => $subjectId, 'amount_baisa' => $debits, 'currency' => $currency, 'occurred_at' => $occurredAt],
            );
            $event = FinancialEvent::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            if ($event->kind !== $kind || $event->subject_type !== $subjectType || $event->subject_id !== $subjectId || $event->amount_baisa !== $debits || $event->currency !== $currency) {
                throw new RuntimeException('Financial event key was reused with different details.');
            }

            $existing = $event->ledgerTransaction()->with('entries')->first();
            if ($existing instanceof LedgerTransaction) {
                return $existing;
            }

            $transaction = $event->ledgerTransaction()->create([
                'description' => LedgerText::description($description),
                'occurred_at' => $occurredAt,
                'currency' => $currency,
                'total_baisa' => $debits,
            ]);

            foreach ($lines as $line) {
                if ($line['debit'] < 0 || $line['credit'] < 0 || ($line['debit'] > 0 && $line['credit'] > 0)) {
                    throw new RuntimeException('Invalid financial event entry.');
                }
                $account = LedgerAccount::query()->firstOrCreate(
                    ['code' => $line['code']],
                    ['name' => LedgerText::accountName($line['code'], $line['name']), 'type' => $line['type'], 'currency' => $currency, 'is_active' => true],
                );
                if ($account->currency !== $currency || $account->type !== $line['type']) {
                    throw new RuntimeException('Ledger account has incompatible currency or type.');
                }
                $transaction->entries()->create([
                    'ledger_account_id' => $account->id,
                    'debit_baisa' => $line['debit'],
                    'credit_baisa' => $line['credit'],
                    'currency' => $currency,
                    'memo' => LedgerText::description($description),
                ]);
            }

            return $transaction->load('entries');
        });
    }
}
