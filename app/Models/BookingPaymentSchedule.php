<?php

namespace App\Models;

use App\Casts\MoneyBaisaCast;
use Brick\Money\Money;
use Database\Factories\BookingPaymentScheduleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $booking_id
 * @property int|null $event_payment_plan_id
 * @property string $plan_name
 * @property string $currency
 * @property int $subtotal_baisa
 * @property int $discount_amount_baisa
 * @property int $total_baisa
 * @property-read Money $subtotal
 * @property-read Money $discount_amount
 * @property-read Money $total
 */
#[Fillable(['booking_id', 'event_payment_plan_id', 'plan_name', 'currency', 'subtotal', 'subtotal_baisa', 'discount_amount', 'discount_amount_baisa', 'total', 'total_baisa'])]
class BookingPaymentSchedule extends Model
{
    /** @use HasFactory<BookingPaymentScheduleFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency' => 'OMR',
        'subtotal_baisa' => 0,
        'discount_amount_baisa' => 0,
        'total_baisa' => 0,
    ];

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<EventPaymentPlan, $this>
     */
    public function eventPaymentPlan(): BelongsTo
    {
        return $this->belongsTo(EventPaymentPlan::class);
    }

    /**
     * @return HasMany<BookingInstallment, $this>
     */
    public function installments(): HasMany
    {
        return $this->hasMany(BookingInstallment::class)->orderBy('sequence');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subtotal' => MoneyBaisaCast::of('subtotal_baisa'),
            'subtotal_baisa' => 'integer',
            'discount_amount' => MoneyBaisaCast::of('discount_amount_baisa'),
            'discount_amount_baisa' => 'integer',
            'total' => MoneyBaisaCast::of('total_baisa'),
            'total_baisa' => 'integer',
        ];
    }
}
