<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Enums\InventoryReservationStatus;
use App\Modules\Store\Models\InventoryReservation;
use App\Modules\Store\Models\ProductOption;
use App\Modules\Store\Settings\StoreSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReserveInventory
{
    /**
     * @param  array<int, int|ProductOption>  $quantities
     */
    public function execute(array $quantities, ?int $durationMinutes = null): InventoryReservation
    {
        $normalized = [];

        foreach ($quantities as $key => $quantity) {
            $optionId = $quantity instanceof ProductOption ? $quantity->getKey() : (int) $key;
            $amount = $quantity instanceof ProductOption ? (int) $key : (int) $quantity;

            if ($optionId < 1 || $amount < 1) {
                throw ValidationException::withMessages(['inventory' => 'Reservation quantities must be positive.']);
            }

            $normalized[$optionId] = ($normalized[$optionId] ?? 0) + $amount;
        }

        if ($normalized === []) {
            throw ValidationException::withMessages(['inventory' => 'At least one inventory item is required.']);
        }

        ksort($normalized);

        return DB::transaction(function () use ($normalized, $durationMinutes): InventoryReservation {
            $options = ProductOption::query()
                ->whereKey(array_keys($normalized))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($options->count() !== count($normalized)) {
                throw ValidationException::withMessages(['inventory' => 'One or more product options do not exist.']);
            }

            foreach ($normalized as $optionId => $quantity) {
                /** @var ProductOption $option */
                $option = $options->get($optionId);

                if (! $option->tracks_inventory) {
                    throw ValidationException::withMessages(['inventory' => 'An untracked option cannot be reserved.']);
                }

                if ($quantity > $option->availableQuantity()) {
                    throw ValidationException::withMessages(['inventory' => 'The requested quantity is unavailable.']);
                }
            }

            $minutes = $durationMinutes ?? app(StoreSettings::class)->reservation_duration_minutes;
            $minutes = max(1, $minutes);
            $reservation = InventoryReservation::query()->create([
                'status' => InventoryReservationStatus::Pending,
                'expires_at' => now()->addMinutes($minutes),
            ]);

            foreach ($normalized as $optionId => $quantity) {
                $reservation->items()->create([
                    'product_option_id' => $optionId,
                    'quantity' => $quantity,
                ]);
            }

            return $reservation->load('items');
        });
    }
}
