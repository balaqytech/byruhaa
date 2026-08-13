<?php

use App\Modules\Store\Actions\AdjustStock;
use App\Modules\Store\Actions\ConsumeInventoryReservation;
use App\Modules\Store\Actions\EnsureDefaultProductOption;
use App\Modules\Store\Actions\ExpireInventoryReservations;
use App\Modules\Store\Actions\PublishProduct;
use App\Modules\Store\Actions\ReleaseInventoryReservation;
use App\Modules\Store\Actions\ReserveInventory;
use App\Modules\Store\Enums\InventoryReservationStatus;
use App\Modules\Store\Enums\ProductStatus;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use Illuminate\Validation\ValidationException;

test('stock adjustments are audited and cannot make tracked stock negative', function (): void {
    $option = Product::factory()->create()->defaultOption()->firstOrFail();
    $option->update(['tracks_inventory' => true, 'stock_on_hand' => 5]);

    $movement = app(AdjustStock::class)->execute($option, 3, 'Opening count');

    expect($movement->quantity_change)->toBe(3)
        ->and($movement->stock_before)->toBe(5)
        ->and($movement->stock_after)->toBe(8)
        ->and($option->fresh()->stock_on_hand)->toBe(8);

    expect(fn (): mixed => app(AdjustStock::class)->execute($option, -9, 'Correction'))
        ->toThrow(ValidationException::class);
});

test('tracked options reserve only available stock and untracked options are skipped', function (): void {
    $option = Product::factory()->create()->defaultOption()->firstOrFail();
    $option->update(['tracks_inventory' => true, 'stock_on_hand' => 5]);

    $reservation = app(ReserveInventory::class)->execute([$option->id => 3]);

    expect($reservation->status)->toBe(InventoryReservationStatus::Pending)
        ->and($option->fresh()->availableQuantity())->toBe(2);

    expect(fn (): mixed => app(ReserveInventory::class)->execute([$option->id => 3]))
        ->toThrow(ValidationException::class);

    $untracked = Product::factory()->create()->defaultOption()->firstOrFail();

    expect(fn (): mixed => app(ReserveInventory::class)->execute([$untracked->id => 1]))
        ->toThrow(ValidationException::class);
});

test('reservation consume and release are idempotent and expiry frees availability', function (): void {
    $option = Product::factory()->create()->defaultOption()->firstOrFail();
    $option->update(['tracks_inventory' => true, 'stock_on_hand' => 4]);
    $reservation = app(ReserveInventory::class)->execute([$option->id => 2], 1);

    app(ConsumeInventoryReservation::class)->execute($reservation);
    app(ConsumeInventoryReservation::class)->execute($reservation);

    expect($reservation->fresh()->status)->toBe(InventoryReservationStatus::Consumed)
        ->and($option->fresh()->stock_on_hand)->toBe(2);

    $releaseReservation = app(ReserveInventory::class)->execute([$option->id => 1], 1);
    app(ReleaseInventoryReservation::class)->execute($releaseReservation);
    app(ReleaseInventoryReservation::class)->execute($releaseReservation);

    expect($releaseReservation->fresh()->status)->toBe(InventoryReservationStatus::Released);

    $expiringReservation = app(ReserveInventory::class)->execute([$option->id => 1], 1);
    $expiringReservation->update(['expires_at' => now()->subMinute()]);
    expect(app(ExpireInventoryReservations::class)->execute())->toBe(1)
        ->and($expiringReservation->fresh()->status)->toBe(InventoryReservationStatus::Expired)
        ->and($option->fresh()->availableQuantity())->toBe(2);
});

test('default option action restores one default and publishing validates the catalogue', function (): void {
    $product = Product::factory()->create();
    $second = ProductOption::factory()->for($product)->create(['is_default' => null, 'price_baisa' => 1500]);
    ProductOption::query()->where('product_id', $product->id)->update(['is_default' => null]);

    $default = app(EnsureDefaultProductOption::class)->execute($product);

    expect($product->fresh()->options()->where('is_default', true)->count())->toBe(1)
        ->and($default->is_default)->toBeTrue();

    expect(fn (): mixed => app(PublishProduct::class)->execute($product))
        ->toThrow(ValidationException::class);

    $default->update(['price_baisa' => 1500]);
    $published = app(PublishProduct::class)->execute($product);

    expect($published->status)->toBe(ProductStatus::Active);
});
