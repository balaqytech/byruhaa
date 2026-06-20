<?php

namespace App\Models;

use App\Casts\MoneyBaisaCast;
use App\Enums\PaymentRefundState;
use App\Enums\PaymentState;
use App\Support\Money\MoneyFactory;
use Brick\Money\Money;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $booking_installment_id
 * @property string $provider
 * @property string $reference
 * @property int $amount_baisa
 * @property string $currency
 * @property-read Money $amount
 * @property PaymentState $state
 * @property string|null $provider_session_id
 * @property string|null $provider_payment_id
 * @property string|null $provider_invoice
 * @property string|null $provider_payment_status
 * @property string|null $checkout_url
 * @property array<string, mixed>|null $request_payload
 * @property array<string, mixed>|null $response_payload
 * @property Carbon|null $verified_at
 * @property Carbon|null $paid_at
 * @property-read int $refundable_amount_baisa
 */
#[Fillable(['booking_installment_id', 'provider', 'reference', 'amount', 'amount_baisa', 'currency', 'state', 'provider_session_id', 'provider_payment_id', 'provider_invoice', 'provider_payment_status', 'checkout_url', 'request_payload', 'response_payload', 'verified_at', 'paid_at'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'provider' => 'thawani',
        'currency' => 'OMR',
        'state' => 'pending',
    ];

    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            $payment->reference ??= 'PAY-'.Str::upper(Str::random(12));
        });
    }

    /**
     * @return BelongsTo<BookingInstallment, $this>
     */
    public function bookingInstallment(): BelongsTo
    {
        return $this->belongsTo(BookingInstallment::class);
    }

    /**
     * @return MorphOne<LedgerTransaction, $this>
     */
    public function ledgerTransaction(): MorphOne
    {
        return $this->morphOne(LedgerTransaction::class, 'source');
    }

    /**
     * @return HasMany<PaymentRefund, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(PaymentRefund::class);
    }

    public function refundableAmountBaisa(): int
    {
        if (! in_array($this->state, [PaymentState::Paid, PaymentState::PartiallyRefunded], true)) {
            return 0;
        }

        $reservedRefundBaisa = (int) ($this->relationLoaded('refunds')
            ? $this->refunds
                ->filter(fn (PaymentRefund $refund): bool => in_array($refund->state, [PaymentRefundState::Pending, PaymentRefundState::Succeeded], true))
                ->sum('amount_baisa')
            : $this->refunds()
                ->whereIn('state', [PaymentRefundState::Pending->value, PaymentRefundState::Succeeded->value])
                ->sum('amount_baisa'));

        $refundableAmount = $this->amount->minus(
            MoneyFactory::fromMinor($reservedRefundBaisa, $this->currency),
        );

        if ($refundableAmount->isNegative()) {
            return 0;
        }

        return MoneyFactory::toMinor($refundableAmount);
    }

    public function isRefundable(): bool
    {
        return $this->refundableAmountBaisa() > 0;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => MoneyBaisaCast::of('amount_baisa'),
            'amount_baisa' => 'integer',
            'state' => PaymentState::class,
            'request_payload' => 'array',
            'response_payload' => 'array',
            'verified_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }
}
