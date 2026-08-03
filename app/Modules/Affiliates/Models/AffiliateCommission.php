<?php

namespace App\Modules\Affiliates\Models;

use App\Casts\MoneyBaisaCast;
use App\Modules\Events\Models\Booking;
use App\Modules\Finance\Models\LedgerTransaction;
use App\Modules\Finance\Models\Payment;
use Brick\Money\Money;
use Database\Factories\AffiliateCommissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $affiliate_id
 * @property int $affiliate_referral_id
 * @property int $booking_id
 * @property int $payment_id
 * @property int $base_amount_baisa
 * @property int $commission_rate_basis_points
 * @property int $commission_amount_baisa
 * @property string $currency
 * @property-read Money $base_amount
 * @property-read Money $commission_amount
 * @property Carbon $earned_at
 */
#[Fillable(['affiliate_id', 'affiliate_referral_id', 'booking_id', 'payment_id', 'base_amount', 'base_amount_baisa', 'commission_rate_basis_points', 'commission_amount', 'commission_amount_baisa', 'currency', 'earned_at'])]
class AffiliateCommission extends Model
{
    /** @use HasFactory<AffiliateCommissionFactory> */
    use HasFactory;

    protected static function newFactory(): AffiliateCommissionFactory
    {
        return AffiliateCommissionFactory::new();
    }

    /**
     * @return BelongsTo<Affiliate, $this>
     */
    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    /**
     * @return BelongsTo<AffiliateReferral, $this>
     */
    public function referral(): BelongsTo
    {
        return $this->belongsTo(AffiliateReferral::class, 'affiliate_referral_id');
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return MorphOne<LedgerTransaction, $this>
     */
    public function ledgerTransaction(): MorphOne
    {
        return $this->morphOne(LedgerTransaction::class, 'source');
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(AffiliateCommissionReversal::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_amount' => MoneyBaisaCast::of('base_amount_baisa'),
            'base_amount_baisa' => 'integer',
            'commission_rate_basis_points' => 'integer',
            'commission_amount' => MoneyBaisaCast::of('commission_amount_baisa'),
            'commission_amount_baisa' => 'integer',
            'earned_at' => 'datetime',
        ];
    }
}
