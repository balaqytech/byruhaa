<?php

namespace App\Modules\Finance\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

#[Fillable(['event_key', 'kind', 'subject_type', 'subject_id', 'amount_baisa', 'currency', 'occurred_at'])]
class FinancialEvent extends Model
{
    protected function casts(): array
    {
        return ['amount_baisa' => 'integer', 'occurred_at' => 'datetime'];
    }

    /** @return MorphOne<LedgerTransaction, $this> */
    public function ledgerTransaction(): MorphOne
    {
        return $this->morphOne(LedgerTransaction::class, 'source');
    }
}
