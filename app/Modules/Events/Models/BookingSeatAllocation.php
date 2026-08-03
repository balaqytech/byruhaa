<?php

namespace App\Modules\Events\Models;

use App\Casts\MoneyBaisaCast;
use App\Enums\SeatAllocationState;
use App\Modules\Finance\Models\Payment;
use Brick\Money\Money;
use Database\Factories\BookingSeatAllocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $booking_id
 * @property int $event_id
 * @property int|null $event_price_tier_id
 * @property int|null $payment_id
 * @property int $seat_count
 * @property SeatAllocationState $state
 * @property string|null $tier_name
 * @property int|null $tier_unit_price_baisa
 * @property-read Money|null $tier_unit_price
 * @property Carbon|null $held_at
 * @property Carbon|null $hold_expires_at
 * @property Carbon|null $reserved_at
 * @property Carbon|null $released_at
 */
#[Fillable(['booking_id', 'event_id', 'event_price_tier_id', 'payment_id', 'seat_count', 'state', 'tier_name', 'tier_unit_price', 'tier_unit_price_baisa', 'held_at', 'hold_expires_at', 'reserved_at', 'released_at'])]
class BookingSeatAllocation extends Model
{
    /** @use HasFactory<BookingSeatAllocationFactory> */
    use HasFactory;

    protected static function newFactory(): BookingSeatAllocationFactory
    {
        return BookingSeatAllocationFactory::new();
    }

    /** @var array<string, mixed> */
    protected $attributes = [
        'state' => 'held',
    ];

    /** @return BelongsTo<Booking, $this> */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** @return BelongsTo<Event, $this> */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /** @return BelongsTo<EventPriceTier, $this> */
    public function priceTier(): BelongsTo
    {
        return $this->belongsTo(EventPriceTier::class, 'event_price_tier_id');
    }

    /** @return BelongsTo<Payment, $this> */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'seat_count' => 'integer',
            'state' => SeatAllocationState::class,
            'tier_unit_price' => MoneyBaisaCast::of('tier_unit_price_baisa'),
            'tier_unit_price_baisa' => 'integer',
            'held_at' => 'datetime',
            'hold_expires_at' => 'datetime',
            'reserved_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }
}
