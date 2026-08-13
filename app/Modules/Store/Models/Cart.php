<?php

namespace App\Modules\Store\Models;

use Database\Factories\CartFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** @property int|null $customer_id */
#[Fillable(['token', 'customer_id', 'last_activity_at'])]
class Cart extends Model
{
    /** @use HasFactory<CartFactory> */
    use HasFactory;

    protected $table = 'store_carts';

    protected static function newFactory(): CartFactory
    {
        return CartFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (Cart $cart): void {
            $cart->token ??= (string) Str::uuid();
            $cart->last_activity_at ??= now();
        });
    }

    /** @return HasMany<CartItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    protected function casts(): array
    {
        return ['last_activity_at' => 'datetime'];
    }
}
