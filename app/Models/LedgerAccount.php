<?php

namespace App\Models;

use App\Enums\LedgerAccountType;
use Database\Factories\LedgerAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property LedgerAccountType $type
 * @property string $currency
 * @property bool $is_active
 */
#[Fillable(['code', 'name', 'type', 'currency', 'is_active'])]
class LedgerAccount extends Model
{
    public const THAWANI_CLEARING_CODE = '1000-THAWANI-CLEARING';

    public const AFFILIATE_PAYOUT_CLEARING_CODE = '1001-AFFILIATE-PAYOUT-CLEARING';

    public const CUSTOMER_DEPOSITS_CODE = '2100-CUSTOMER-DEPOSITS';

    public const AFFILIATE_COMMISSION_LIABILITY_CODE = '2200-AFFILIATE-COMMISSIONS';

    public const AFFILIATE_COMMISSION_EXPENSE_CODE = '5100-AFFILIATE-COMMISSION-EXPENSE';

    /** @use HasFactory<LedgerAccountFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency' => 'OMR',
        'is_active' => true,
    ];

    /**
     * @return HasMany<LedgerEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LedgerAccountType::class,
            'is_active' => 'boolean',
        ];
    }
}
