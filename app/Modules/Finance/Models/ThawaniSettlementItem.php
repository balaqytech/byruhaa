<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['thawani_settlement_id', 'provider_operation_reference', 'kind', 'amount_baisa'])]
class ThawaniSettlementItem extends Model
{
    /** @return BelongsTo<ThawaniSettlement, $this> */
    public function settlement(): BelongsTo
    {
        return $this->belongsTo(ThawaniSettlement::class, 'thawani_settlement_id');
    }

    protected function casts(): array
    {
        return ['amount_baisa' => 'integer'];
    }
}
