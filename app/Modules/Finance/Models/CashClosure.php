<?php

namespace App\Modules\Finance\Models;

use App\Modules\Identity\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

#[Fillable(['cashier_user_id', 'business_date', 'opening_float_baisa', 'receipts_baisa', 'refunds_baisa', 'withdrawals_baisa', 'expected_baisa', 'counted_baisa', 'difference_baisa', 'status', 'issues', 'closed_by_user_id', 'notes'])]
class CashClosure extends Model implements AuditableContract
{
    use AuditableTrait;

    /** @return BelongsTo<User, $this> */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_user_id');
    }

    protected function casts(): array
    {
        return [
            'business_date' => 'date',
            'opening_float_baisa' => 'integer',
            'receipts_baisa' => 'integer',
            'refunds_baisa' => 'integer',
            'withdrawals_baisa' => 'integer',
            'expected_baisa' => 'integer',
            'counted_baisa' => 'integer',
            'difference_baisa' => 'integer',
            'issues' => 'array',
        ];
    }
}
