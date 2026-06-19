<?php

namespace App\Models;

use App\Enums\BookingInstallmentState;
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
 * @property BookingInstallmentState $state
 * @property Carbon|null $paid_at
 */
#[Fillable(['booking_payment_schedule_id', 'name', 'sequence', 'percentage', 'due_date', 'gross_amount_baisa', 'discount_amount_baisa', 'amount_baisa', 'state', 'paid_at'])]
class BookingInstallment extends Model
{
    /** @use HasFactory<BookingInstallmentFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'gross_amount_baisa' => 0,
        'discount_amount_baisa' => 0,
        'amount_baisa' => 0,
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
            'gross_amount_baisa' => 'integer',
            'discount_amount_baisa' => 'integer',
            'amount_baisa' => 'integer',
            'state' => BookingInstallmentState::class,
            'paid_at' => 'datetime',
        ];
    }
}
