<?php

namespace App\Modules\Finance\Models;

use App\Casts\MoneyBaisaCast;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $wallet_id
 * @property int|null $payment_id
 * @property string $reference
 * @property string $operation_key
 * @property string $status
 * @property string $currency
 * @property int $amount_baisa
 * @property int $refundable_baisa
 * @property int $reserved_refund_baisa
 * @property int $spendable_baisa
 * @property-read Money $amount
 * @property-read Money $refundableAmount
 * @property Carbon|null $credited_at
 * @property Carbon|null $refund_deadline_at
 */
#[Fillable(['wallet_id', 'payment_id', 'reference', 'operation_key', 'status', 'currency', 'amount', 'amount_baisa', 'refundable_baisa', 'reserved_refund_baisa', 'spendable_baisa', 'credited_at', 'refund_deadline_at'])]
class WalletTopUp extends Model
{
    protected $table = 'wallet_top_ups';

    protected static function booted(): void
    {
        static::creating(function (WalletTopUp $topUp): void {
            $topUp->reference ??= 'WAL-'.Str::upper(Str::random(12));
        });
    }

    /**
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return HasMany<WalletPurchaseAllocation, $this>
     */
    public function purchaseAllocations(): HasMany
    {
        return $this->hasMany(WalletPurchaseAllocation::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => MoneyBaisaCast::of('amount_baisa'),
            'amount_baisa' => 'integer',
            'refundable_baisa' => 'integer',
            'reserved_refund_baisa' => 'integer',
            'spendable_baisa' => 'integer',
            'credited_at' => 'datetime',
            'refund_deadline_at' => 'datetime',
        ];
    }
}
