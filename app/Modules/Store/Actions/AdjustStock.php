<?php

namespace App\Modules\Store\Actions;

use App\Modules\Store\Models\InventoryMovement;
use App\Modules\Store\Models\ProductOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdjustStock
{
    public function execute(ProductOption $option, int $quantityChange, string $reason, ?int $actorUserId = null): InventoryMovement
    {
        if ($quantityChange === 0 || blank(trim($reason))) {
            throw ValidationException::withMessages(['stock' => 'A non-zero quantity and a reason are required.']);
        }

        return DB::transaction(function () use ($option, $quantityChange, $reason, $actorUserId): InventoryMovement {
            $option = ProductOption::query()->whereKey($option->getKey())->lockForUpdate()->firstOrFail();

            if (! $option->tracks_inventory) {
                throw ValidationException::withMessages(['stock' => 'Inventory tracking is disabled for this option.']);
            }

            $before = $option->stock_on_hand;
            $after = $before + $quantityChange;

            if ($after < $option->reservedQuantity()) {
                throw ValidationException::withMessages(['stock' => 'Stock on hand cannot be lower than active reservations.']);
            }

            $option->forceFill(['stock_on_hand' => $after])->save();

            return InventoryMovement::query()->create([
                'product_option_id' => $option->getKey(),
                'actor_user_id' => $actorUserId,
                'quantity_change' => $quantityChange,
                'stock_before' => $before,
                'stock_after' => $after,
                'reason' => trim($reason),
            ]);
        });
    }
}
