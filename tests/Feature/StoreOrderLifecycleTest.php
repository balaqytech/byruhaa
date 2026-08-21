<?php

use App\Modules\Identity\Models\Customer;
use App\Modules\Store\Actions\AddCartItem;
use App\Modules\Store\Actions\ChangeOrderState;
use App\Modules\Store\Actions\CreateOrder;
use App\Modules\Store\Actions\ExpirePendingOrders;
use App\Modules\Store\Actions\RemoveCartItem;
use App\Modules\Store\Actions\ResolveCart;
use App\Modules\Store\Actions\UpdateCartItem;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use App\Modules\Store\Settings\StoreSettings;
use App\Modules\Store\States\Order\Accepted;
use App\Modules\Store\States\Order\Confirmed;
use App\Modules\Store\States\Order\Preparing;
use Illuminate\Validation\ValidationException;

function storeProductOption(array $attributes = []): ProductOption
{
    $product = Product::factory()->create(['status' => 'active']);
    $option = $product->defaultOption()->firstOrFail();
    $option->forceFill(array_merge([
        'name' => 'Standard',
        'sku' => 'STORE-'.fake()->unique()->numerify('#####'),
        'price_baisa' => 1000,
        'is_available' => true,
        'tracks_inventory' => false,
        'stock_on_hand' => 0,
    ], $attributes))->save();

    return $option->load('product.category');
}

function storeOrderData(string $key = 'order-key'): array
{
    return [
        'idempotency_key' => $key,
        'customer_name' => 'Mona Said',
        'customer_phone' => '+96891234567',
        'customer_email' => 'mona@example.com',
        'pickup_type' => 'immediate',
    ];
}

function enableStoreOrdering(): void
{
    $settings = app(StoreSettings::class);
    $settings->ordering_enabled = true;
    $settings->save();
}

test('guest carts can add update and remove available items without reserving inventory', function (): void {
    $option = storeProductOption(['tracks_inventory' => true, 'stock_on_hand' => 5]);
    $cart = Cart::factory()->create();

    $item = app(AddCartItem::class)->execute($cart, $option, 2, 'Less ice');

    expect($item->quantity)->toBe(2)
        ->and($option->fresh()->availableQuantity())->toBe(5);

    $item = app(UpdateCartItem::class)->execute($cart, $item, 3, 'More ice');
    expect($item->quantity)->toBe(3)->and($item->note)->toBe('More ice');

    app(RemoveCartItem::class)->execute($cart, $item);

    expect($cart->items()->count())->toBe(0);
});

test('customer carts require the owning customer when resolved by token', function (): void {
    $customer = Customer::factory()->create();
    $cart = Cart::factory()->create(['customer_id' => $customer->id]);

    expect(fn (): mixed => app(ResolveCart::class)->execute($cart->token, null, false))
        ->toThrow(ValidationException::class);

    expect(app(ResolveCart::class)->execute($cart->token, $customer->id, false)->id)->toBe($cart->id);
});

test('cart rejects draft unavailable and zero-price products', function (): void {
    $cart = Cart::factory()->create();
    $draft = storeProductOption(['price_baisa' => 0]);
    $draft->product->update(['status' => 'draft']);

    expect(fn (): mixed => app(AddCartItem::class)->execute($cart, $draft, 1))
        ->toThrow(ValidationException::class);

    $unavailable = storeProductOption(['is_available' => false]);
    expect(fn (): mixed => app(AddCartItem::class)->execute($cart, $unavailable, 1))
        ->toThrow(ValidationException::class);
});

test('order creation calculates VAT from settings and snapshots mutable product data', function (): void {
    enableStoreOrdering();
    $option = storeProductOption(['price_baisa' => 1000]);
    $originalProductName = $option->product->name;
    $cart = Cart::factory()->create();
    app(AddCartItem::class)->execute($cart, $option, 2);

    $order = app(CreateOrder::class)->execute($cart, storeOrderData());

    expect($order->subtotal_baisa)->toBe(1905)
        ->and($order->vat_baisa)->toBe(95)
        ->and($order->total_baisa)->toBe(2000)
        ->and($order->status->getValue())->toBe('pending_payment')
        ->and($order->statusHistory()->count())->toBe(1);

    $option->product->update(['name' => 'Changed product']);
    $option->update(['name' => 'Changed option', 'price_baisa' => 2500]);

    expect($order->items()->firstOrFail())
        ->product_name->toBe($originalProductName)
        ->option_name->toBe('Standard')
        ->unit_price_baisa->toBe(1000);
});

test('idempotent order creation returns the same order and cannot cross customer ownership', function (): void {
    enableStoreOrdering();
    $option = storeProductOption();
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();
    $cart = Cart::factory()->create(['customer_id' => $customer->id]);
    app(AddCartItem::class)->execute($cart, $option, 1);
    $data = [...storeOrderData('same-key'), 'customer_id' => $customer->id];

    $first = app(CreateOrder::class)->execute($cart, $data);
    $second = app(CreateOrder::class)->execute($cart, $data);

    expect($second->id)->toBe($first->id)->and(Order::query()->count())->toBe(1);

    expect(fn (): mixed => app(CreateOrder::class)->execute($cart, [...$data, 'customer_id' => $otherCustomer->id]))
        ->toThrow(ValidationException::class);
});

test('tracked and untracked options share an order while only tracked stock is reserved', function (): void {
    enableStoreOrdering();
    $tracked = storeProductOption(['tracks_inventory' => true, 'stock_on_hand' => 2]);
    $untracked = storeProductOption(['tracks_inventory' => false]);
    $cart = Cart::factory()->create();
    app(AddCartItem::class)->execute($cart, $tracked, 2);
    app(AddCartItem::class)->execute($cart, $untracked, 1);

    $order = app(CreateOrder::class)->execute($cart, storeOrderData('mixed-key'));

    expect($order->items)->toHaveCount(2)
        ->and($order->inventoryReservation)->not->toBeNull()
        ->and($order->inventoryReservation->reservation->items)->toHaveCount(1)
        ->and($tracked->fresh()->availableQuantity())->toBe(0)
        ->and($untracked->fresh()->availableQuantity())->toBeNull();
});

test('insufficient tracked stock rolls back order creation and cart contents remain', function (): void {
    enableStoreOrdering();
    $option = storeProductOption(['tracks_inventory' => true, 'stock_on_hand' => 1]);
    $cart = Cart::factory()->create();
    app(AddCartItem::class)->execute($cart, $option, 2);

    expect(fn (): mixed => app(CreateOrder::class)->execute($cart, storeOrderData('rollback-key')))
        ->toThrow(ValidationException::class);

    expect(Order::query()->count())->toBe(0)
        ->and($cart->items()->count())->toBe(1)
        ->and($option->fresh()->reservedQuantity())->toBe(0);
});

test('scheduled pickup must be four hours ahead and within configured opening hours', function (): void {
    enableStoreOrdering();
    $settings = app(StoreSettings::class);
    $settings->opening_time = '08:00';
    $settings->closing_time = '22:00';
    $settings->save();
    $option = storeProductOption();
    $cart = Cart::factory()->create();
    app(AddCartItem::class)->execute($cart, $option, 1);

    expect(fn (): mixed => app(CreateOrder::class)->execute($cart, [...storeOrderData('too-soon'), 'pickup_type' => 'scheduled', 'pickup_at' => now()->addHours(3)]))
        ->toThrow(ValidationException::class);

    expect(fn (): mixed => app(CreateOrder::class)->execute($cart, [...storeOrderData('outside-hours'), 'pickup_type' => 'scheduled', 'pickup_at' => now()->addDays(1)->setTime(23, 0)]))
        ->toThrow(ValidationException::class);

    $order = app(CreateOrder::class)->execute($cart, [...storeOrderData('valid-scheduled'), 'pickup_type' => 'scheduled', 'pickup_at' => now()->addDay()->setTime(12, 0)]);

    expect($order->pickup_at)->not->toBeNull();
});

test('order state transitions follow legal pickup rules and write history', function (): void {
    enableStoreOrdering();
    $settings = app(StoreSettings::class);
    $settings->opening_time = '08:00';
    $settings->closing_time = '22:00';
    $settings->save();
    $option = storeProductOption();
    $cart = Cart::factory()->create();
    app(AddCartItem::class)->execute($cart, $option, 1);
    $order = app(CreateOrder::class)->execute($cart, storeOrderData('state-key'));

    expect($order->status->getField())->toBe('status')
        ->and($order->status->getValue())->toBe('pending_payment')
        ->and($order->status->canTransitionTo(Confirmed::class))->toBeTrue()
        ->and(Order::query()->whereKey($order->id)->firstOrFail()->status->canTransitionTo(Confirmed::class))->toBeTrue();

    $confirmed = app(ChangeOrderState::class)->execute($order, Confirmed::class);
    expect($confirmed->status->getValue())->toBe('confirmed')
        ->and($confirmed->status->canTransitionTo(Preparing::class))->toBeTrue();
    app(ChangeOrderState::class)->execute($order, Preparing::class);

    expect($order->refresh()->status->getValue())->toBe('preparing')
        ->and($order->statusHistory()->count())->toBe(3);

    $scheduledCart = Cart::factory()->create();
    app(AddCartItem::class)->execute($scheduledCart, $option, 1);
    $scheduled = app(CreateOrder::class)->execute($scheduledCart, [...storeOrderData('scheduled-state'), 'pickup_type' => 'scheduled', 'pickup_at' => now()->addDay()->setTime(12, 0)]);
    app(ChangeOrderState::class)->execute($scheduled, Confirmed::class);

    expect(fn (): mixed => app(ChangeOrderState::class)->execute($scheduled, Preparing::class))
        ->toThrow(ValidationException::class);

    app(ChangeOrderState::class)->execute($scheduled, Accepted::class);
    expect($scheduled->refresh()->status->getValue())->toBe('accepted');
});

test('pending orders expire and release their inventory reservation', function (): void {
    enableStoreOrdering();
    $option = storeProductOption(['tracks_inventory' => true, 'stock_on_hand' => 2]);
    $cart = Cart::factory()->create();
    app(AddCartItem::class)->execute($cart, $option, 1);
    $order = app(CreateOrder::class)->execute($cart, storeOrderData('expire-key'));
    $order->forceFill(['created_at' => now()->subMinutes(3)])->save();

    expect(app(ExpirePendingOrders::class)->execute())->toBe(1)
        ->and($order->refresh()->status->getValue())->toBe('expired')
        ->and($order->inventoryReservation->reservation->fresh()->status->value)->toBeIn(['released', 'expired']);
});
