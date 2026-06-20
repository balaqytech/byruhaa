<?php

namespace App\Models;

use App\Casts\MoneyBaisaCast;
use Brick\Money\Money;
use Database\Factories\LedgerEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $ledger_transaction_id
 * @property int $ledger_account_id
 * @property int $debit_baisa
 * @property int $credit_baisa
 * @property string $currency
 * @property-read Money $debit
 * @property-read Money $credit
 * @property string|null $memo
 */
#[Fillable(['ledger_transaction_id', 'ledger_account_id', 'debit', 'debit_baisa', 'credit', 'credit_baisa', 'currency', 'memo'])]
class LedgerEntry extends Model
{
    /** @use HasFactory<LedgerEntryFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'debit_baisa' => 0,
        'credit_baisa' => 0,
        'currency' => 'OMR',
    ];

    /**
     * @return BelongsTo<LedgerTransaction, $this>
     */
    public function ledgerTransaction(): BelongsTo
    {
        return $this->belongsTo(LedgerTransaction::class);
    }

    /**
     * @return BelongsTo<LedgerAccount, $this>
     */
    public function ledgerAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'debit' => MoneyBaisaCast::of('debit_baisa'),
            'debit_baisa' => 'integer',
            'credit' => MoneyBaisaCast::of('credit_baisa'),
            'credit_baisa' => 'integer',
        ];
    }
}
