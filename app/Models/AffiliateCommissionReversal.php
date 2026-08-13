<?php

namespace App\Models;

use App\Modules\Affiliates\Models\AffiliateCommission;
use App\Modules\Finance\Models\LedgerTransaction;
use Database\Factories\AffiliateCommissionReversalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

#[Fillable(['affiliate_commission_id', 'event_cancellation_id', 'amount_baisa', 'currency', 'reason', 'reversed_at'])]
class AffiliateCommissionReversal extends Model
{
    /** @use HasFactory<AffiliateCommissionReversalFactory> */
    use HasFactory;

    /** @return BelongsTo<AffiliateCommission, $this> */
    public function commission(): BelongsTo
    {
        return $this->belongsTo(AffiliateCommission::class, 'affiliate_commission_id');
    }

    /** @return BelongsTo<EventCancellation, $this> */
    public function eventCancellation(): BelongsTo
    {
        return $this->belongsTo(EventCancellation::class);
    }

    /** @return MorphOne<LedgerTransaction, $this> */
    public function ledgerTransaction(): MorphOne
    {
        return $this->morphOne(LedgerTransaction::class, 'source');
    }

    protected function casts(): array
    {
        return ['reversed_at' => 'datetime', 'amount_baisa' => 'integer'];
    }
}
