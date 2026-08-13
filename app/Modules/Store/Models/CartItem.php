<?php

namespace App\Modules\Store\Models;

use Database\Factories\CartItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['cart_id', 'product_option_id', 'quantity', 'note'])]
class CartItem extends Model
{
    /** @use HasFactory<CartItemFactory> */
    use HasFactory;

    protected $table = 'store_cart_items';

    protected static function newFactory(): CartItemFactory
    {
        return CartItemFactory::new();
    }

    /** @return BelongsTo<Cart, $this> */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /** @return BelongsTo<ProductOption, $this> */
    public function productOption(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class);
    }

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }
}
