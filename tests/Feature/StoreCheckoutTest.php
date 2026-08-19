<?php

use App\Livewire\Store\Checkout;
use App\Modules\Store\Actions\AddCartItem;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\Category;
use App\Modules\Store\Models\Product;
use Livewire\Livewire;

test('the dedicated checkout page is publicly reachable', function (): void {
    $this->get(route('store.checkout'))
        ->assertSuccessful()
        ->assertSee('store-checkout-title', false);
});

test('checkout page displays the current cart summary and customer form', function (): void {
    $category = Category::factory()->create();
    $product = Product::factory()->active()->create([
        'category_id' => $category->id,
        'name' => 'Checkout Coffee',
    ]);
    $option = $product->defaultOption()->firstOrFail();
    $option->update(['price_baisa' => 1500]);

    $cart = Cart::factory()->create();
    app(AddCartItem::class)->execute($cart, $option, 1);
    session()->put('store_cart_token', $cart->token);

    Livewire::test(Checkout::class)
        ->assertSee('Checkout Coffee')
        ->assertSee('إتمام الطلب')
        ->assertSee('تأكيد الطلب والمتابعة إلى الدفع');
});
