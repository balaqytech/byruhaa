<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['wallet_withdrawal_request_id', 'wallet_top_up_id', 'amount_baisa'])]
class WalletWithdrawalAllocation extends Model
{
    /** @return BelongsTo<WalletWithdrawalRequest, $this> */
    public function withdrawal(): BelongsTo
    {
        return $this->belongsTo(WalletWithdrawalRequest::class, 'wallet_withdrawal_request_id');
    }

    /** @return BelongsTo<WalletTopUp, $this> */
    public function topUp(): BelongsTo
    {
        return $this->belongsTo(WalletTopUp::class, 'wallet_top_up_id');
    }
}
