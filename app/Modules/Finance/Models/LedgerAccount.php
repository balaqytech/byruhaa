<?php

namespace App\Modules\Finance\Models;

use App\Enums\LedgerAccountType;
use App\Support\LedgerText;
use Database\Factories\LedgerAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property LedgerAccountType $type
 * @property string $currency
 * @property bool $is_active
 */
#[Fillable(['code', 'name', 'type', 'currency', 'is_active'])]
class LedgerAccount extends Model
{
    public const THAWANI_CLEARING_CODE = '1000-THAWANI-CLEARING';

    public const AFFILIATE_PAYOUT_CLEARING_CODE = '1001-AFFILIATE-PAYOUT-CLEARING';

    public const CUSTOMER_DEPOSITS_CODE = '2100-CUSTOMER-DEPOSITS';

    public const CASH_ON_HAND_CODE = '1002-CASH-ON-HAND';

    public const BANK_CODE = '1003-BANK';

    public const WALLET_LIABILITY_CODE = '2110-WALLET-LIABILITY';

    public const STORE_DEFERRED_CODE = '2120-STORE-DEFERRED';

    public const VAT_PAYABLE_CODE = '2300-VAT-PAYABLE';

    public const STORE_SALES_CODE = '4000-STORE-SALES';

    public const STORE_SALES_RETURNS_CODE = '4090-STORE-SALES-RETURNS';

    public const THAWANI_FEES_CODE = '5200-THAWANI-FEES';

    public const AFFILIATE_COMMISSION_LIABILITY_CODE = '2200-AFFILIATE-COMMISSIONS';

    public const AFFILIATE_COMMISSION_EXPENSE_CODE = '5100-AFFILIATE-COMMISSION-EXPENSE';

    /** @use HasFactory<LedgerAccountFactory> */
    use HasFactory;

    protected static function newFactory(): LedgerAccountFactory
    {
        return LedgerAccountFactory::new();
    }

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency' => 'OMR',
        'is_active' => true,
    ];

    /**
     * @return HasMany<LedgerEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function displayName(): string
    {
        return LedgerText::accountName($this->code, $this->name);
    }

    public function currentBalanceBaisa(): int
    {
        $totals = $this->entries()
            ->selectRaw('COALESCE(SUM(debit_baisa), 0) AS debits, COALESCE(SUM(credit_baisa), 0) AS credits')
            ->first();
        $debits = (int) $totals?->getAttribute('debits');
        $credits = (int) $totals?->getAttribute('credits');

        return in_array($this->type, [LedgerAccountType::Asset, LedgerAccountType::Expense], true)
            ? $debits - $credits
            : $credits - $debits;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LedgerAccountType::class,
            'is_active' => 'boolean',
        ];
    }
}
