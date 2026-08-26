<?php

namespace App\Modules\Events\Models;

use App\Casts\MoneyBaisaCast;
use App\Models\WebhookDelivery;
use App\Modules\Affiliates\Models\AffiliateReferral;
use App\Modules\Events\Actions\ReleaseBookingSeats;
use App\Modules\Events\Services\CouponUsageService;
use App\Modules\Events\States\Booking\BookingState;
use App\Modules\Events\States\Booking\Cancelled;
use App\Modules\Events\States\Booking\Rejected;
use App\Modules\Events\States\Contract\Signed;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\User;
use App\Services\Webhooks\ByruhaaWebhookSender;
use Brick\Money\Money;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\ModelStates\HasStates;

/**
 * @property int $id
 * @property int $customer_id
 * @property int $event_id
 * @property string $reference
 * @property BookingState $state
 * @property int|null $reviewed_by_user_id
 * @property Carbon|null $reviewed_at
 * @property string|null $review_notes
 * @property string|null $cancellation_reason
 * @property int $unit_price_baisa
 * @property string $currency
 * @property int $family_member_count
 * @property int $subtotal_baisa
 * @property int|null $discount_id
 * @property int|null $coupon_id
 * @property string|null $coupon_code
 * @property string|null $discount_name
 * @property int $discount_amount_baisa
 * @property int $total_baisa
 * @property-read Money $unit_price
 * @property-read Money $subtotal
 * @property-read Money $discount_amount
 * @property-read Money $total
 */
#[Fillable(['customer_id', 'event_id', 'reference', 'state', 'reviewed_by_user_id', 'reviewed_at', 'review_notes', 'cancellation_reason', 'unit_price', 'unit_price_baisa', 'currency', 'family_member_count', 'subtotal', 'subtotal_baisa', 'discount_id', 'coupon_id', 'coupon_code', 'discount_name', 'discount_amount', 'discount_amount_baisa', 'total', 'total_baisa'])]
class Booking extends Model implements AuditableContract
{
    /** @use HasFactory<BookingFactory> */
    use AuditableTrait, HasFactory, HasStates;

    /**
     * @var array<int, string>
     */
    protected $auditInclude = [
        'customer_id',
        'event_id',
        'reference',
        'state',
        'reviewed_by_user_id',
        'reviewed_at',
        'review_notes',
        'cancellation_reason',
        'unit_price_baisa',
        'currency',
        'family_member_count',
        'subtotal_baisa',
        'discount_id',
        'coupon_id',
        'coupon_code',
        'discount_name',
        'discount_amount_baisa',
        'total_baisa',
    ];

    protected static function newFactory(): BookingFactory
    {
        return BookingFactory::new();
    }

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'unit_price_baisa' => 0,
        'currency' => 'OMR',
        'family_member_count' => 0,
        'subtotal_baisa' => 0,
        'discount_amount_baisa' => 0,
        'total_baisa' => 0,
    ];

    protected static function booted(): void
    {
        static::creating(function (Booking $booking): void {
            $booking->reference ??= 'BRH-'.Str::upper(Str::random(8));
        });

        static::updated(function (Booking $booking): void {
            if (! $booking->wasChanged('state')) {
                return;
            }

            if ($booking->state instanceof Cancelled || $booking->state instanceof Rejected) {
                app(CouponUsageService::class)->releaseForBooking($booking);
                DB::afterCommit(function () use ($booking): void {
                    app(ReleaseBookingSeats::class)->execute($booking);

                    if ($booking->state instanceof Cancelled) {
                        app(ByruhaaWebhookSender::class)->sendBookingCancelled($booking);
                    }
                });
            }
        });
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    /**
     * @return BelongsTo<Discount, $this>
     */
    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * @return HasOne<CouponRedemption, $this>
     */
    public function couponRedemption(): HasOne
    {
        return $this->hasOne(CouponRedemption::class);
    }

    /**
     * @return HasMany<BookingFamilyMember, $this>
     */
    public function familyMembers(): HasMany
    {
        return $this->hasMany(BookingFamilyMember::class);
    }

    /**
     * @return HasOne<BookingPaymentSchedule, $this>
     */
    public function paymentSchedule(): HasOne
    {
        return $this->hasOne(BookingPaymentSchedule::class);
    }

    /**
     * @return HasOne<BookingSeatAllocation, $this>
     */
    public function seatAllocation(): HasOne
    {
        return $this->hasOne(BookingSeatAllocation::class);
    }

    /**
     * @return HasOne<AffiliateReferral, $this>
     */
    public function affiliateReferral(): HasOne
    {
        return $this->hasOne(AffiliateReferral::class);
    }

    /**
     * @return MorphMany<WebhookDelivery, $this>
     */
    public function webhookDeliveries(): MorphMany
    {
        return $this->morphMany(WebhookDelivery::class, 'webhookable');
    }

    /**
     * @return HasManyThrough<BookingInstallment, BookingPaymentSchedule, $this>
     */
    public function installments(): HasManyThrough
    {
        return $this->hasManyThrough(BookingInstallment::class, BookingPaymentSchedule::class)
            ->orderBy('sequence');
    }

    public function hasSignedContracts(): bool
    {
        $familyMembers = $this->relationLoaded('familyMembers')
            ? $this->familyMembers
            : $this->familyMembers()->with('contract')->get();

        return $familyMembers->isNotEmpty()
            && $familyMembers->every(fn (BookingFamilyMember $familyMember): bool => $familyMember->contract?->state instanceof Signed);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'state' => BookingState::class,
            'reviewed_at' => 'datetime',
            'unit_price' => MoneyBaisaCast::of('unit_price_baisa'),
            'unit_price_baisa' => 'integer',
            'family_member_count' => 'integer',
            'subtotal' => MoneyBaisaCast::of('subtotal_baisa'),
            'subtotal_baisa' => 'integer',
            'discount_amount' => MoneyBaisaCast::of('discount_amount_baisa'),
            'discount_amount_baisa' => 'integer',
            'total' => MoneyBaisaCast::of('total_baisa'),
            'total_baisa' => 'integer',
        ];
    }

    public function discountSource(): ?string
    {
        if ($this->coupon_id !== null || filled($this->coupon_code)) {
            return 'coupon';
        }

        if ($this->discount_id !== null || filled($this->discount_name)) {
            return 'discount';
        }

        return null;
    }
}
