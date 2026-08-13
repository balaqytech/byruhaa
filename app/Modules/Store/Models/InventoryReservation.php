<?php

namespace App\Modules\Store\Models;

use App\Modules\Store\Enums\InventoryReservationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property InventoryReservationStatus $status
 * @property Carbon $expires_at
 */
#[Fillable(['reference', 'status', 'expires_at', 'consumed_at', 'released_at'])]
class InventoryReservation extends Model
{
    protected $table = 'store_inventory_reservations';

    protected $attributes = [
        'status' => InventoryReservationStatus::Pending->value,
    ];

    protected static function booted(): void
    {
        static::creating(function (InventoryReservation $reservation): void {
            $reservation->reference ??= (string) Str::uuid();
        });
    }

    /** @return HasMany<InventoryReservationItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(InventoryReservationItem::class, 'reservation_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => InventoryReservationStatus::class,
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }
}
