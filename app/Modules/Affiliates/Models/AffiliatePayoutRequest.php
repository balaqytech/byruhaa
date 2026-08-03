<?php

namespace App\Modules\Affiliates\Models;

use App\Casts\MoneyBaisaCast;
use App\Enums\AffiliatePayoutRequestStatus;
use App\Modules\Finance\Models\LedgerTransaction;
use App\Modules\Identity\Models\User;
use Brick\Money\Money;
use Database\Factories\AffiliatePayoutRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $affiliate_id
 * @property string $reference
 * @property int $amount_baisa
 * @property string $currency
 * @property-read Money $amount
 * @property AffiliatePayoutRequestStatus $status
 * @property string|null $affiliate_notes
 * @property array<string, mixed>|null $payment_details
 * @property string|null $admin_notes
 * @property int|null $approved_by_user_id
 * @property Carbon|null $approved_at
 * @property int|null $paid_by_user_id
 * @property Carbon|null $paid_at
 * @property int|null $rejected_by_user_id
 * @property Carbon|null $rejected_at
 */
#[Fillable(['affiliate_id', 'reference', 'amount', 'amount_baisa', 'currency', 'status', 'affiliate_notes', 'payment_details', 'admin_notes', 'approved_by_user_id', 'approved_at', 'paid_by_user_id', 'paid_at', 'rejected_by_user_id', 'rejected_at'])]
class AffiliatePayoutRequest extends Model
{
    /** @use HasFactory<AffiliatePayoutRequestFactory> */
    use HasFactory;

    protected static function newFactory(): AffiliatePayoutRequestFactory
    {
        return AffiliatePayoutRequestFactory::new();
    }

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency' => 'OMR',
        'status' => 'pending',
    ];

    protected static function booted(): void
    {
        static::creating(function (AffiliatePayoutRequest $payoutRequest): void {
            $payoutRequest->reference ??= 'APO-'.Str::upper(Str::random(12));
        });
    }

    /**
     * @return BelongsTo<Affiliate, $this>
     */
    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by_user_id');
    }

    /**
     * @return MorphOne<LedgerTransaction, $this>
     */
    public function ledgerTransaction(): MorphOne
    {
        return $this->morphOne(LedgerTransaction::class, 'source');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => MoneyBaisaCast::of('amount_baisa'),
            'amount_baisa' => 'integer',
            'status' => AffiliatePayoutRequestStatus::class,
            'payment_details' => 'array',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }
}
