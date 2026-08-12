<?php

namespace App\Modules\Events\Models;

use App\Casts\MoneyBaisaCast;
use Brick\Money\Money;
use Database\Factories\EventPriceTierFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

/**
 * @property int $id
 * @property int $event_id
 * @property string $name
 * @property int $position
 * @property int $seat_capacity
 * @property int $price_baisa
 * @property string $currency
 * @property-read Money $price
 * @property bool $is_active
 */
#[Fillable(['event_id', 'name', 'position', 'seat_capacity', 'price', 'price_baisa', 'currency', 'is_active'])]
class EventPriceTier extends Model
{
    /** @use HasFactory<EventPriceTierFactory> */
    use HasFactory, SoftDeletes;

    protected static function newFactory(): EventPriceTierFactory
    {
        return EventPriceTierFactory::new();
    }

    /** @var array<string, mixed> */
    protected $attributes = [
        'position' => 1,
        'currency' => 'OMR',
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::saving(function (EventPriceTier $tier): void {
            $event = $tier->event()->first();

            if ($event instanceof Event) {
                $tier->currency = $event->currency;
            }

            $otherTiers = self::query()
                ->where('event_id', $tier->event_id)
                ->when($tier->exists, fn ($query) => $query->whereKeyNot($tier->id));

            if ($event instanceof Event && (int) $otherTiers->sum('seat_capacity') + $tier->seat_capacity > $event->seat_capacity) {
                throw ValidationException::withMessages([
                    'price_tiers' => __('ui.messages.price_tier_capacity_exceeded'),
                ]);
            }

            if ($tier->exists && $tier->isDirty('seat_capacity') && $tier->seat_capacity < $tier->usedSeatsCount()) {
                throw ValidationException::withMessages([
                    'price_tiers' => __('ui.messages.price_tier_capacity_below_usage'),
                ]);
            }

            $previousPrice = (clone $otherTiers)->where('position', '<', $tier->position)->max('price_baisa');
            $nextPrice = (clone $otherTiers)->where('position', '>', $tier->position)->min('price_baisa');

            if (($previousPrice !== null && $tier->price_baisa < (int) $previousPrice)
                || ($nextPrice !== null && $tier->price_baisa > (int) $nextPrice)) {
                throw ValidationException::withMessages([
                    'price_tiers' => __('ui.messages.price_tier_prices_must_increase'),
                ]);
            }
        });

        static::deleting(function (EventPriceTier $tier): void {
            if ($tier->usedSeatsCount() > 0) {
                throw ValidationException::withMessages([
                    'price_tiers' => __('ui.messages.price_tier_in_use'),
                ]);
            }
        });
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return HasMany<BookingSeatAllocation, $this> */
    public function seatAllocations(): HasMany
    {
        return $this->hasMany(BookingSeatAllocation::class);
    }

    public function usedSeatsCount(): int
    {
        if (array_key_exists('unavailable_seats_count', $this->attributes)) {
            return (int) $this->attributes['unavailable_seats_count'];
        }

        return (int) $this->seatAllocations()
            ->whereIn('state', ['held', 'reserved'])
            ->sum('seat_count');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'seat_capacity' => 'integer',
            'price' => MoneyBaisaCast::of('price_baisa'),
            'price_baisa' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
