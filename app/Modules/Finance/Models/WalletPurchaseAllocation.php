<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $wallet_id
 * @property int $wallet_top_up_id
 * @property string $order_reference
 * @property int $amount_baisa
 * @property Carbon|null $refund_deadline_at
 * @property Carbon|null $reversed_at
 */
#[Fillable(['wallet_id', 'wallet_top_up_id', 'order_reference', 'amount_baisa', 'refund_deadline_at', 'reversed_at'])]
class WalletPurchaseAllocation extends Model
{
    protected $table = 'wallet_purchase_allocations';

    /**
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * @return BelongsTo<WalletTopUp, $this>
     */
    public function topUp(): BelongsTo
    {
        return $this->belongsTo(WalletTopUp::class, 'wallet_top_up_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_baisa' => 'integer',
            'refund_deadline_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }
}
