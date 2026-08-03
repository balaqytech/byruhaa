<?php

namespace App\Modules\Finance\Models;

use App\Casts\MoneyBaisaCast;
use App\Enums\PaymentRefundState;
use Brick\Money\Money;
use Database\Factories\PaymentRefundFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $payment_id
 * @property string $reference
 * @property int $amount_baisa
 * @property string $currency
 * @property-read Money $amount
 * @property PaymentRefundState $state
 * @property string|null $provider_refund_id
 * @property string|null $provider_payment_id
 * @property string|null $provider_status
 * @property string $reason
 * @property array<string, mixed>|null $request_payload
 * @property array<string, mixed>|null $response_payload
 * @property Carbon|null $processed_at
 * @property string|null $resolution_method
 * @property string|null $manual_reference
 * @property string|null $manual_notes
 * @property string|null $manual_evidence_path
 * @property Carbon|null $manual_required_at
 * @property Carbon|null $manually_completed_at
 * @property int|null $manually_completed_by_user_id
 */
#[Fillable(['payment_id', 'reference', 'amount', 'amount_baisa', 'currency', 'state', 'resolution_method', 'provider_refund_id', 'provider_payment_id', 'provider_status', 'reason', 'request_payload', 'response_payload', 'processed_at', 'manual_reference', 'manual_notes', 'manual_evidence_path', 'manual_required_at', 'manually_completed_at', 'manually_completed_by_user_id'])]
class PaymentRefund extends Model
{
    /** @use HasFactory<PaymentRefundFactory> */
    use HasFactory;

    protected static function newFactory(): PaymentRefundFactory
    {
        return PaymentRefundFactory::new();
    }

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency' => 'OMR',
        'state' => 'pending',
    ];

    protected static function booted(): void
    {
        static::creating(function (PaymentRefund $paymentRefund): void {
            $paymentRefund->reference ??= 'REF-'.Str::upper(Str::random(12));
        });
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * @return MorphOne<LedgerTransaction, $this>
     */
    public function ledgerTransaction(): MorphOne
    {
        return $this->morphOne(LedgerTransaction::class, 'source');
    }

    /** @return MorphMany<WebhookDelivery, $this> */
    public function webhookDeliveries(): MorphMany
    {
        return $this->morphMany(WebhookDelivery::class, 'webhookable');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => MoneyBaisaCast::of('amount_baisa'),
            'amount_baisa' => 'integer',
            'state' => PaymentRefundState::class,
            'request_payload' => 'array',
            'response_payload' => 'array',
            'processed_at' => 'datetime',
            'manual_required_at' => 'datetime',
            'manually_completed_at' => 'datetime',
        ];
    }
}
