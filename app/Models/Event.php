<?php

namespace App\Models;

use App\Casts\MoneyBaisaCast;
use App\Enums\EventEnrollmentStatus;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\Enums\SeatAllocationState;
use App\States\Booking\Approved;
use Brick\Money\Money;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property EventType $type
 * @property EventStatus $status
 * @property string|null $landing_page_key
 * @property string|null $subtitle
 * @property string|null $excerpt
 * @property array<int, string>|null $card_topics
 * @property string|null $description_html
 * @property string|null $contract_terms_html
 * @property array<int, array<string, mixed>>|null $participant_extra_fields
 * @property string|null $location
 * @property string|null $schedule_text
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property int $minimum_age
 * @property int $maximum_age
 * @property int $seat_capacity
 * @property int $price_baisa
 * @property string $currency
 * @property-read Money $price
 */
#[Fillable(['name', 'slug', 'type', 'status', 'enrollment_status', 'landing_page_key', 'subtitle', 'excerpt', 'card_topics', 'description_html', 'contract_terms_html', 'participant_extra_fields', 'location', 'schedule_text', 'starts_at', 'ends_at', 'minimum_age', 'maximum_age', 'seat_capacity', 'price', 'price_baisa', 'currency'])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $appends = ['price'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'price_baisa' => 0,
        'currency' => 'OMR',
        'enrollment_status' => EventEnrollmentStatus::BookingOpen->value,
    ];

    protected static function booted(): void
    {
        static::saving(function (Event $event): void {
            if (! $event->exists) {
                return;
            }

            if ($event->isDirty('seat_capacity')) {
                $minimumCapacity = max(
                    $event->unavailableSeatsCount(),
                    (int) $event->priceTiers()->sum('seat_capacity'),
                );

                if ($event->seat_capacity < $minimumCapacity) {
                    throw ValidationException::withMessages([
                        'seat_capacity' => __('ui.messages.event_capacity_below_usage'),
                    ]);
                }
            }

            if ($event->isDirty('price_baisa') && (int) $event->priceTiers()->max('price_baisa') > $event->price_baisa) {
                throw ValidationException::withMessages([
                    'price' => __('ui.messages.event_price_below_tiers'),
                ]);
            }
        });
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * @return HasMany<Discount, $this>
     */
    public function discounts(): HasMany
    {
        return $this->hasMany(Discount::class);
    }

    /**
     * @return HasMany<Coupon, $this>
     */
    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    /**
     * @return HasMany<EventPaymentPlan, $this>
     */
    public function paymentPlans(): HasMany
    {
        return $this->hasMany(EventPaymentPlan::class);
    }

    /**
     * @return HasMany<EventPriceTier, $this>
     */
    public function priceTiers(): HasMany
    {
        return $this->hasMany(EventPriceTier::class)->orderBy('position');
    }

    /**
     * @return HasMany<BookingSeatAllocation, $this>
     */
    public function seatAllocations(): HasMany
    {
        return $this->hasMany(BookingSeatAllocation::class);
    }

    public function eventInterests(): HasMany
    {
        return $this->hasMany(EventInterest::class);
    }

    public function canExpressInterest(): bool
    {
        return $this->enrollment_status->canExpressInterest();
    }

    public function canBook(): bool
    {
        return $this->enrollment_status->canBook();
    }

    public function isComingSoon(): bool
    {
        return $this->enrollment_status === EventEnrollmentStatus::ComingSoon;
    }

    public function approvedSeatsCount(): int
    {
        return BookingFamilyMember::query()
            ->whereHas('booking', fn ($query) => $query
                ->where('event_id', $this->id)
                ->where('state', Approved::$name))
            ->count();
    }

    public function remainingSeats(): int
    {
        return max(0, $this->seat_capacity - $this->unavailableSeatsCount());
    }

    public function reservedSeatsCount(): int
    {
        return (int) $this->seatAllocations()
            ->where('state', SeatAllocationState::Reserved->value)
            ->sum('seat_count');
    }

    public function heldSeatsCount(): int
    {
        return (int) $this->seatAllocations()
            ->where('state', SeatAllocationState::Held->value)
            ->sum('seat_count');
    }

    public function unavailableSeatsCount(): int
    {
        if (array_key_exists('unavailable_seats_count', $this->attributes)) {
            return (int) $this->attributes['unavailable_seats_count'];
        }

        return (int) $this->seatAllocations()
            ->whereIn('state', [SeatAllocationState::Held->value, SeatAllocationState::Reserved->value])
            ->sum('seat_count');
    }

    public function isPublished(): bool
    {
        return $this->status === EventStatus::Published;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EventStatus::class,
            'enrollment_status' => EventEnrollmentStatus::class,
            'type' => EventType::class,
            'participant_extra_fields' => 'array',
            'card_topics' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'minimum_age' => 'integer',
            'maximum_age' => 'integer',
            'seat_capacity' => 'integer',
            'price' => MoneyBaisaCast::of('price_baisa'),
            'price_baisa' => 'integer',
        ];
    }
}
