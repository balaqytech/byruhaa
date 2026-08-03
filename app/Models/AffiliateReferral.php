<?php

namespace App\Models;

use App\Modules\Events\Models\Booking;
use Database\Factories\AffiliateReferralFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $affiliate_id
 * @property int $booking_id
 * @property string $affiliate_code
 * @property string $affiliate_name
 * @property Carbon $captured_at
 * @property Carbon $expires_at
 * @property Carbon $attributed_at
 */
#[Fillable(['affiliate_id', 'booking_id', 'affiliate_code', 'affiliate_name', 'captured_at', 'expires_at', 'attributed_at'])]
class AffiliateReferral extends Model
{
    /** @use HasFactory<AffiliateReferralFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Affiliate, $this>
     */
    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return HasMany<AffiliateCommission, $this>
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'captured_at' => 'datetime',
            'expires_at' => 'datetime',
            'attributed_at' => 'datetime',
        ];
    }
}
