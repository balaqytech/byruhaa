<?php

namespace App\Modules\Finance\Models;

use App\Casts\MoneyBaisaCast;
use App\Modules\Finance\Enums\WalletSettlementStatus;
use Brick\Money\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $wallet_id
 * @property string $order_reference
 * @property int $amount_baisa
 * @property string $currency
 * @property WalletSettlementStatus $status
 * @property-read Money $amount
 * @property Carbon $eligible_at
 * @property Carbon|null $transferred_at
 * @property string|null $transfer_reference
 */
#[Fillable(['wallet_id', 'order_reference', 'amount', 'amount_baisa', 'currency', 'status', 'eligible_at', 'transferred_at', 'transfer_reference'])]
class WalletSettlement extends Model
{
    protected $table = 'wallet_settlements';

    /**
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => MoneyBaisaCast::of('amount_baisa'),
            'amount_baisa' => 'integer',
            'status' => WalletSettlementStatus::class,
            'eligible_at' => 'datetime',
            'transferred_at' => 'datetime',
        ];
    }
}
