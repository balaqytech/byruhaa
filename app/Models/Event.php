<?php

namespace App\Models;

use App\Casts\MoneyBaisaCast;
use App\Enums\EventStatus;
use App\Enums\EventType;
use App\States\Booking\Approved;
use Brick\Money\Money;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property EventType $type
 * @property EventStatus $status
 * @property string|null $landing_page_key
 * @property string|null $excerpt
 * @property string|null $description_html
 * @property string|null $contract_terms_html
 * @property array<int, array<string, mixed>>|null $participant_extra_fields
 * @property string|null $location
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property int $minimum_age
 * @property int $maximum_age
 * @property int $seat_capacity
 * @property int $price_baisa
 * @property string $currency
 * @property-read Money $price
 */
#[Fillable(['name', 'slug', 'type', 'status', 'landing_page_key', 'excerpt', 'description_html', 'contract_terms_html', 'participant_extra_fields', 'location', 'starts_at', 'ends_at', 'minimum_age', 'maximum_age', 'seat_capacity', 'price', 'price_baisa', 'currency'])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'price_baisa' => 0,
        'currency' => 'OMR',
    ];

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
        return max(0, $this->seat_capacity - $this->approvedSeatsCount());
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
            'type' => EventType::class,
            'participant_extra_fields' => 'array',
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
