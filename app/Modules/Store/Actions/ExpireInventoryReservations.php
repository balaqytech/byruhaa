<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Enums\InventoryReservationStatus;
use Illuminate\Support\Facades\DB;

class ExpireInventoryReservations
{
    public function execute(): int
    {
        return DB::table('store_inventory_reservations')
            ->where('status', InventoryReservationStatus::Pending->value)
            ->where('expires_at', '<=', now())
            ->update(['status' => InventoryReservationStatus::Expired->value, 'updated_at' => now()]);
    }
}
