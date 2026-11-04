<?php

namespace App\Modules\Store\Models;

use App\Casts\MoneyBaisaCast;
use Brick\Money\Money;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read Money $unit_price
 * @property int $regular_unit_price_baisa
 * @property int $unit_discount_baisa
 * @property int $line_discount_baisa
 */
#[Fillable(['order_id', 'product_option_id', 'product_name', 'option_name', 'sku', 'currency', 'unit_price', 'unit_price_baisa', 'regular_unit_price_baisa', 'unit_discount_baisa', 'quantity', 'vat', 'vat_baisa', 'line_subtotal', 'line_subtotal_baisa', 'line_total', 'line_total_baisa', 'line_discount_baisa', 'note'])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    protected $table = 'store_order_items';

    protected static function newFactory(): OrderItemFactory
    {
        return OrderItemFactory::new();
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<ProductOption, $this> */
    public function productOption(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class);
    }

    protected function casts(): array
    {
        return [
            'unit_price' => MoneyBaisaCast::of('unit_price_baisa'),
            'unit_price_baisa' => 'integer',
            'regular_unit_price_baisa' => 'integer',
            'unit_discount_baisa' => 'integer',
            'quantity' => 'integer',
            'vat' => MoneyBaisaCast::of('vat_baisa'),
            'vat_baisa' => 'integer',
            'line_subtotal' => MoneyBaisaCast::of('line_subtotal_baisa'),
            'line_subtotal_baisa' => 'integer',
            'line_total' => MoneyBaisaCast::of('line_total_baisa'),
            'line_total_baisa' => 'integer',
            'line_discount_baisa' => 'integer',
        ];
    }
}
