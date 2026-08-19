<?php

use App\Livewire\Store\CoffeeStore;
use App\Modules\Store\Actions\AddCartItem;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use App\Modules\Store\Settings\StoreSettings;
use Livewire\Livewire;

function coffeeStoreOption(array $attributes = []): ProductOption
{
    $product = Product::factory()->create(['status' => 'active']);
    $option = $product->defaultOption()->firstOrFail();
    $option->forceFill(array_merge([
        'name' => 'قهوة اليوم',
        'sku' => 'COFFEE-'.fake()->unique()->numerify('#####'),
        'price_baisa' => 1200,
        'currency' => 'OMR',
        'is_available' => true,
        'tracks_inventory' => false,
        'stock_on_hand' => 0,
    ], $attributes))->save();

    return $option->load('product.category');
}

test('guest storefront keeps an opaque cart token in the session', function (): void {
    $option = coffeeStoreOption();

    Livewire::test(CoffeeStore::class)
        ->call('addToCart', $option->id)
        ->assertSet('cartToken', fn (mixed $token): bool => is_string($token) && $token !== '');

    expect(session('store_cart_token'))->toBeString()
        ->and(Cart::query()->where('token', session('store_cart_token'))->firstOrFail()->items)->toHaveCount(1);
});

test('storefront rejects unavailable inventory before checkout', function (): void {
    $option = coffeeStoreOption(['tracks_inventory' => true, 'stock_on_hand' => 1]);
    $cart = Cart::factory()->create();
    app(AddCartItem::class)->execute($cart, $option, 1);
    $cart->items()->firstOrFail()->forceFill(['quantity' => 2])->save();

    session()->put('store_cart_token', $cart->token);

    Livewire::test(CoffeeStore::class)
        ->call('openCheckout')
        ->assertSet('checkoutOpen', false)
        ->assertHasErrors('cart');
});

test('opening checkout replaces an idempotency key left by a previous order', function (): void {
    $option = coffeeStoreOption();
    $cart = Cart::factory()->create();
    app(AddCartItem::class)->execute($cart, $option, 1);

    session()->put('store_cart_token', $cart->token);
    session()->put('store_checkout_idempotency_key', 'previous-order-key');

    Livewire::test(CoffeeStore::class)
        ->call('openCheckout')
        ->assertSet('checkoutOpen', true);

    $checkoutKeys = session('store_checkout_idempotency_keys', []);

    expect(session()->has('store_checkout_idempotency_key'))->toBeFalse()
        ->and($checkoutKeys)->toBeArray()->toHaveCount(1)
        ->and(array_values($checkoutKeys)[0])->toBeString()->not->toBe('previous-order-key');
});

test('changing the cart discards the current checkout attempt', function (): void {
    $option = coffeeStoreOption();
    $component = Livewire::test(CoffeeStore::class)
        ->call('addToCart', $option->id)
        ->call('openCheckout');

    expect(session('store_checkout_idempotency_keys', []))->toHaveCount(1);

    $component->call('addToCart', $option->id);

    expect(session('store_checkout_idempotency_keys', []))->toBeEmpty();
});

test('reopening checkout keeps the key for an unchanged cart', function (): void {
    $option = coffeeStoreOption();
    $component = Livewire::test(CoffeeStore::class)
        ->call('addToCart', $option->id)
        ->call('openCheckout');

    $firstKey = array_values(session('store_checkout_idempotency_keys', []))[0];

    $component->call('openCheckout');

    expect(array_values(session('store_checkout_idempotency_keys', []))[0])->toBe($firstKey);
});

test('storefront disables checkout while ordering is disabled', function (): void {
    $settings = app(StoreSettings::class);
    $settings->ordering_enabled = false;
    $settings->save();

    $response = $this->get(route('coffee'));

    $response->assertSuccessful()
        ->assertSee('wire:click="openCheckout"', false)
        ->assertSee('disabled', false);
});
