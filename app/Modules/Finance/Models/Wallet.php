<?php

namespace App\Modules\Finance\Models;

use App\Modules\Identity\Models\MinorProfile;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $minor_profile_id
 * @property string $currency
 * @property int $balance_baisa
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['minor_profile_id', 'currency', 'balance_baisa', 'status'])]
class Wallet extends Model
{
    protected $table = 'wallets';

    protected $attributes = [
        'currency' => 'OMR',
        'balance_baisa' => 0,
        'status' => 'active',
    ];

    /** @return BelongsTo<MinorProfile, $this> */
    public function minorProfile(): BelongsTo
    {
        return $this->belongsTo(MinorProfile::class);
    }

    /**
     * @return HasMany<WalletTopUp, $this>
     */
    public function topUps(): HasMany
    {
        return $this->hasMany(WalletTopUp::class);
    }

    /**
     * @return HasMany<WalletMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(WalletMovement::class);
    }

    /**
     * @return HasMany<WalletPurchaseAllocation, $this>
     */
    public function purchaseAllocations(): HasMany
    {
        return $this->hasMany(WalletPurchaseAllocation::class);
    }

    /**
     * @return HasMany<WalletSettlement, $this>
     */
    public function settlements(): HasMany
    {
        return $this->hasMany(WalletSettlement::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balance_baisa' => 'integer',
        ];
    }
}
