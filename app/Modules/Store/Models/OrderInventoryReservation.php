<?php

namespace App\Modules\Store\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'reservation_id'])]
class OrderInventoryReservation extends Model
{
    protected $table = 'store_order_inventory_reservations';

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<InventoryReservation, $this> */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(InventoryReservation::class);
    }
}
