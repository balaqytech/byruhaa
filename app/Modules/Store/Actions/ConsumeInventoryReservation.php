<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Enums\InventoryReservationStatus;
use App\Modules\Store\Models\InventoryMovement;
use App\Modules\Store\Models\InventoryReservation;
use App\Modules\Store\Models\ProductOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConsumeInventoryReservation
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

            $items = $reservation->items()->orderBy('product_option_id')->get();
            $optionIds = $items->pluck('product_option_id')->sort()->values()->all();
            $options = ProductOption::query()
                ->whereKey($optionIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $option = $options->get($item->product_option_id);

                if (! $option instanceof ProductOption || ! $option->tracks_inventory || $option->stock_on_hand < $item->quantity) {
                    throw ValidationException::withMessages(['inventory' => 'Reserved stock is no longer available.']);
                }

                $before = $option->stock_on_hand;
                $after = $before - $item->quantity;
                $option->forceFill(['stock_on_hand' => $after])->save();
                InventoryMovement::query()->create([
                    'product_option_id' => $option->getKey(),
                    'quantity_change' => -$item->quantity,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'reason' => 'Reservation '.$reservation->reference.' consumed',
                ]);
            }

            $reservation->forceFill([
                'status' => InventoryReservationStatus::Consumed,
                'consumed_at' => now(),
            ])->save();

            return $reservation;
        });
    }
}
