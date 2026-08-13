<?php

namespace App\Modules\Store\Models;

use App\Casts\MoneyBaisaCast;
use App\Modules\Store\States\Order\OrderState;
use Brick\Money\Money;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\ModelStates\HasStates;

/**
 * @property OrderState $status
 * @property Carbon|null $pickup_at
 * @property-read Money $subtotal
 * @property-read Money $vat
 * @property-read Money $total
 */
#[Fillable(['reference', 'idempotency_key', 'customer_id', 'status', 'currency', 'customer_name', 'customer_phone', 'customer_email', 'recipient_name', 'recipient_phone', 'note', 'pickup_type', 'pickup_at', 'subtotal', 'subtotal_baisa', 'vat', 'vat_baisa', 'total', 'total_baisa'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, HasStates;

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
        ];
    }
}
