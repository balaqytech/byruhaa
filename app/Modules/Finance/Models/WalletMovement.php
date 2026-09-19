<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $wallet_id
 * @property int|null $wallet_top_up_id
 * @property string $operation_key
 * @property string $type
 * @property string|null $order_reference
 * @property int $credit_baisa
 * @property int $debit_baisa
 * @property int $balance_after_baisa
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['wallet_id', 'wallet_top_up_id', 'operation_key', 'type', 'order_reference', 'credit_baisa', 'debit_baisa', 'balance_after_baisa', 'metadata'])]
class WalletMovement extends Model
{
    protected $table = 'wallet_movements';

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
            'credit_baisa' => 'integer',
            'debit_baisa' => 'integer',
            'balance_after_baisa' => 'integer',
            'metadata' => 'array',
        ];
    }
}
