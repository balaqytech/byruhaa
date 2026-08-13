<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Enums\InventoryReservationStatus;
use App\Modules\Store\Models\InventoryReservation;
use Illuminate\Support\Facades\DB;

class ReleaseInventoryReservation
{
    public function execute(InventoryReservation $reservation): InventoryReservation
    {
        return DB::transaction(function () use ($reservation): InventoryReservation {
            $reservation = InventoryReservation::query()->whereKey($reservation->getKey())->lockForUpdate()->firstOrFail();

            if ($reservation->status !== InventoryReservationStatus::Pending) {
                return $reservation;
            }

            if ($reservation->expires_at->isPast()) {
                $reservation->forceFill(['status' => InventoryReservationStatus::Expired])->save();

                return $reservation;
            }

            $reservation->forceFill([
                'status' => InventoryReservationStatus::Released,
                'released_at' => now(),
            ])->save();

            return $reservation;
        });
    }
}
