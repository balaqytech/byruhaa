<?php

namespace App\Modules\Store\Models;

use App\Casts\MoneyBaisaCast;
use App\Models\WebhookDelivery;
use App\Modules\Store\States\Order\OrderState;
use Brick\Money\Money;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Spatie\ModelStates\HasStates;

/**
 * @property OrderState $status
 * @property string $payment_token
 * @property int|null $minor_profile_id
 * @property Carbon|null $pickup_at
 * @property-read Money $subtotal
 * @property-read Money $vat
 * @property-read Money $total
 */
#[Fillable(['reference', 'payment_token', 'idempotency_key', 'customer_id', 'minor_profile_id', 'status', 'currency', 'customer_name', 'customer_phone', 'customer_email', 'recipient_name', 'recipient_phone', 'note', 'pickup_type', 'pickup_at', 'subtotal', 'subtotal_baisa', 'vat', 'vat_baisa', 'total', 'total_baisa', 'vat_rate_percentage', 'seller_legal_name', 'seller_tax_number', 'seller_address', 'seller_phone', 'receipt_footer', 'paid_at', 'payment_reference', 'provider_invoice'])]
class Order extends Model implements AuditableContract
{
    /** @use HasFactory<OrderFactory> */
    use AuditableTrait, HasFactory, HasStates;

    /**
     * @var array<int, string>
     */
    protected $auditInclude = [
        'reference',
        'customer_id',
        'minor_profile_id',
        'status',
        'currency',
        'customer_name',
        'customer_phone',
        'customer_email',
        'recipient_name',
        'recipient_phone',
        'note',
        'pickup_type',
        'pickup_at',
        'subtotal_baisa',
        'vat_baisa',
        'total_baisa',
        'vat_rate_percentage',
        'paid_at',
        'payment_reference',
    ];

    protected $table = 'store_orders';

    protected static function newFactory(): OrderFactory
    {
        return OrderFactory::new();
    }

    protected $attributes = [
        'currency' => 'OMR',
        'pickup_type' => 'immediate',
        'subtotal_baisa' => 0,
        'vat_baisa' => 0,
        'total_baisa' => 0,
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order): void {
            $order->reference ??= 'BRH-ORD-'.Str::upper(Str::random(10));
            $order->payment_token ??= (string) Str::uuid();
        });
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasMany<OrderStatusHistory, $this> */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    /** @return HasOne<OrderInventoryReservation, $this> */
    public function inventoryReservation(): HasOne
    {
        return $this->hasOne(OrderInventoryReservation::class);
    }

    /** @return MorphMany<WebhookDelivery, $this> */
    public function webhookDeliveries(): MorphMany
    {
        return $this->morphMany(WebhookDelivery::class, 'webhookable');
    }

    protected function casts(): array
    {
        return [
            'status' => OrderState::class,
            'pickup_at' => 'datetime',
            'subtotal' => MoneyBaisaCast::of('subtotal_baisa'),
            'subtotal_baisa' => 'integer',
            'vat' => MoneyBaisaCast::of('vat_baisa'),
            'vat_baisa' => 'integer',
            'total' => MoneyBaisaCast::of('total_baisa'),
            'total_baisa' => 'integer',
            'vat_rate_percentage' => 'integer',
            'paid_at' => 'datetime',
        ];
    }
}
