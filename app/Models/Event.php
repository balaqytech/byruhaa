<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\States\Booking\Approved;
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
 * @property string $type
 * @property EventStatus $status
 * @property string|null $excerpt
 * @property string|null $description_html
 * @property string|null $contract_terms_html
 * @property string|null $location
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property int $minimum_age
 * @property int $maximum_age
 * @property int $seat_capacity
 * @property int $price_baisa
 * @property string $currency
 */
#[Fillable(['name', 'slug', 'type', 'status', 'excerpt', 'description_html', 'contract_terms_html', 'location', 'starts_at', 'ends_at', 'minimum_age', 'maximum_age', 'seat_capacity', 'price_baisa', 'currency'])]
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
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'minimum_age' => 'integer',
            'maximum_age' => 'integer',
            'seat_capacity' => 'integer',
            'price_baisa' => 'integer',
        ];
    }
}
