<?php

namespace App\Modules\Finance\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['provider_reference', 'gross_baisa', 'fee_baisa', 'bank_net_baisa', 'currency', 'settled_at', 'statement_path', 'recorded_by_user_id'])]
class ThawaniSettlement extends Model
{
    /** @return HasMany<ThawaniSettlementItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ThawaniSettlementItem::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    protected function casts(): array
    {
        return ['gross_baisa' => 'integer', 'fee_baisa' => 'integer', 'bank_net_baisa' => 'integer', 'settled_at' => 'datetime'];
    }
}
