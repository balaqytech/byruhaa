<?php

namespace App\Models;

use App\Casts\MoneyBaisaCast;
use App\Enums\CouponType;
use App\Support\Money\MoneyFactory;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Carbon\CarbonInterface;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $event_id
 * @property string $code
 * @property string $name
 * @property CouponType $type
 * @property int|null $amount_baisa
 * @property-read Money|null $amount
 * @property int|null $percentage_basis_points
 * @property string $currency
 * @property Carbon $expires_at
 * @property int $minimum_family_members
 * @property int|null $maximum_family_members
 * @property int|null $maximum_uses
 * @property int|null $maximum_uses_per_customer
 * @property bool $is_active
 */
#[Fillable(['event_id', 'code', 'name', 'type', 'amount', 'amount_baisa', 'percentage_basis_points', 'currency', 'expires_at', 'minimum_family_members', 'maximum_family_members', 'maximum_uses', 'maximum_uses_per_customer', 'is_active'])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency' => 'OMR',
        'minimum_family_members' => 1,
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::saving(function (Coupon $coupon): void {
            $coupon->code = self::normalizeCode($coupon->code);
            $coupon->currency = strtoupper($coupon->currency);

            if ($coupon->type === CouponType::FixedAmountPerMember) {
                $coupon->percentage_basis_points = null;
            }

            if ($coupon->type === CouponType::PercentagePerMember) {
                $coupon->amount_baisa = null;
            }
        });
    }

    public static function normalizeCode(string $code): string
    {
        return Str::of($code)->trim()->upper()->replace(' ', '')->toString();
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return HasMany<CouponRedemption, $this>
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function activeRedemptionsCount(): int
    {
        return (int) $this->redemptions()
            ->whereNull('released_at')
            ->count();
    }

    public function activeRedemptionsCountForCustomer(int $customerId): int
    {
        return (int) $this->redemptions()
            ->where('customer_id', $customerId)
            ->whereNull('released_at')
            ->count();
    }

    public function amountForFamilyMembers(int $familyMemberCount, Money $subtotal): Money
    {
        $familyMemberCount = max(0, $familyMemberCount);

        $amount = match ($this->type) {
            CouponType::FixedAmountPerMember => $this->fixedAmountForFamilyMembers($familyMemberCount),
            CouponType::PercentagePerMember => $this->percentageAmountForSubtotal($subtotal),
        };

        return Money::min($subtotal, $amount);
    }

    /**
     * @param  Builder<Coupon>  $query
     * @return Builder<Coupon>
     */
    public function scopeMatchingCode(Builder $query, string $code): Builder
    {
        return $query->where('code', self::normalizeCode($code));
    }

    /**
     * @param  Builder<Coupon>  $query
     * @return Builder<Coupon>
     */
    public function scopeEligibleFor(Builder $query, Event $event, int $familyMemberCount, CarbonInterface $bookedAt): Builder
    {
        return $query
            ->whereBelongsTo($event)
            ->where('is_active', true)
            ->where('expires_at', '>=', $bookedAt)
            ->where('minimum_family_members', '<=', $familyMemberCount)
            ->where(fn (Builder $query) => $query
                ->whereNull('maximum_family_members')
                ->orWhere('maximum_family_members', '>=', $familyMemberCount));
    }

    private function fixedAmountForFamilyMembers(int $familyMemberCount): Money
    {
        $amountPerFamilyMember = $this->amount ?? MoneyFactory::zero($this->currency);

        if ($amountPerFamilyMember->isNegative()) {
            $amountPerFamilyMember = MoneyFactory::zero($this->currency);
        }

        return $amountPerFamilyMember->multipliedBy($familyMemberCount);
    }

    private function percentageAmountForSubtotal(Money $subtotal): Money
    {
        $basisPoints = max(0, min(10000, (int) $this->percentage_basis_points));

        return $subtotal->multipliedBy($basisPoints)->dividedBy(10000, RoundingMode::DOWN);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'amount' => MoneyBaisaCast::of('amount_baisa'),
            'amount_baisa' => 'integer',
            'percentage_basis_points' => 'integer',
            'expires_at' => 'datetime',
            'minimum_family_members' => 'integer',
            'maximum_family_members' => 'integer',
            'maximum_uses' => 'integer',
            'maximum_uses_per_customer' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
