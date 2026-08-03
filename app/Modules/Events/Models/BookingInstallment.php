<?php

namespace App\Modules\Events\Models;

use App\Casts\MoneyBaisaCast;
use App\Enums\BookingInstallmentState;
use App\Modules\Finance\Models\Payment;
use Brick\Money\Money;
use Database\Factories\BookingInstallmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $booking_payment_schedule_id
 * @property string|null $name
 * @property int $sequence
 * @property int $percentage
 * @property Carbon $due_date
 * @property int $gross_amount_baisa
 * @property int $discount_amount_baisa
 * @property int $amount_baisa
 * @property string $currency
 * @property-read Money $gross_amount
 * @property-read Money $discount_amount
 * @property-read Money $amount
 * @property BookingInstallmentState $state
 * @property Carbon|null $paid_at
 */
#[Fillable(['booking_payment_schedule_id', 'name', 'sequence', 'percentage', 'due_date', 'gross_amount', 'gross_amount_baisa', 'discount_amount', 'discount_amount_baisa', 'amount', 'amount_baisa', 'currency', 'state', 'paid_at'])]
class BookingInstallment extends Model
{
    /** @use HasFactory<BookingInstallmentFactory> */
    use HasFactory;

    protected static function newFactory(): BookingInstallmentFactory
    {
        return BookingInstallmentFactory::new();
    }

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'gross_amount_baisa' => 0,
        'discount_amount_baisa' => 0,
        'amount_baisa' => 0,
        'currency' => 'OMR',
        'state' => 'pending',
    ];

    /**
     * @return BelongsTo<BookingPaymentSchedule, $this>
     */
    public function paymentSchedule(): BelongsTo
    {
        return $this->belongsTo(BookingPaymentSchedule::class, 'booking_payment_schedule_id');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'percentage' => 'integer',
            'due_date' => 'date',
            'gross_amount' => MoneyBaisaCast::of('gross_amount_baisa'),
            'gross_amount_baisa' => 'integer',
            'discount_amount' => MoneyBaisaCast::of('discount_amount_baisa'),
            'discount_amount_baisa' => 'integer',
            'amount' => MoneyBaisaCast::of('amount_baisa'),
            'amount_baisa' => 'integer',
            'state' => BookingInstallmentState::class,
            'paid_at' => 'datetime',
        ];
    }
}
