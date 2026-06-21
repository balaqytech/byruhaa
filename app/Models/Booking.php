<?php

namespace App\Models;

use App\Casts\MoneyBaisaCast;
use App\States\Booking\BookingState;
use App\States\Contract\Signed;
use Brick\Money\Money;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
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
 * @property int $unit_price_baisa
 * @property string $currency
 * @property int $family_member_count
 * @property int $subtotal_baisa
 * @property int|null $discount_id
 * @property string|null $discount_name
 * @property int $discount_amount_baisa
 * @property int $total_baisa
 * @property-read Money $unit_price
 * @property-read Money $subtotal
 * @property-read Money $discount_amount
 * @property-read Money $total
 */
#[Fillable(['customer_id', 'event_id', 'reference', 'state', 'reviewed_by_user_id', 'reviewed_at', 'review_notes', 'unit_price', 'unit_price_baisa', 'currency', 'family_member_count', 'subtotal', 'subtotal_baisa', 'discount_id', 'discount_name', 'discount_amount', 'discount_amount_baisa', 'total', 'total_baisa'])]
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory, HasStates;

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
}
