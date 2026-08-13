<?php

use App\Modules\Identity\Models\Customer;
use App\Modules\Store\Actions\ReorderStoreOrder;
use App\Modules\Store\Enums\OrderStatus;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use App\Modules\Store\Services\StoreReceiptRenderer;
use App\Modules\Store\Settings\StoreSettings;
use Illuminate\Support\Str;

function customerStoreOrder(Customer $customer, array $attributes = []): Order
{
    $order = Order::factory()->create(array_merge([
        'customer_id' => $customer->id,
        'status' => OrderStatus::Confirmed->value,
        'payment_token' => (string) Str::uuid(),
        'paid_at' => now(),
        'vat_rate_percentage' => 5,
        'seller_legal_name' => 'بيرحاء للتجارب',
        'seller_tax_number' => 'OM123',
    ], $attributes));

    $option = ProductOption::factory()->default()->create(['price_baisa' => 1000]);
    $order->items()->create([
        'product_option_id' => $option->id,
        'product_name' => $option->product->name,
        'option_name' => $option->name,
        'sku' => $option->sku,
        'currency' => 'OMR',
        'unit_price_baisa' => 1000,
        'quantity' => 1,
        'vat_baisa' => 50,
        'line_subtotal_baisa' => 1000,
        'line_total_baisa' => 1050,
        'note' => 'بدون سكر',
    ]);
    $order->statusHistory()->create(['to_status' => $order->status->getValue(), 'note' => 'تم الدفع']);

    return $order->load(['items', 'statusHistory']);
}

test('customer order history and details are strictly owned and use opaque tokens', function (): void {
    $customer = Customer::factory()->create();
    $other = Customer::factory()->create();
    $order = customerStoreOrder($customer, ['created_at' => now()->subDay()]);
    $otherOrder = customerStoreOrder($other);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.store.orders.index'))
        ->assertSuccessful()
        ->assertSee($order->reference)
        ->assertDontSee($otherOrder->reference);

    $this->get(route('customer.store.orders.show', $order->payment_token))
        ->assertSuccessful()
        ->assertSee($order->reference)
        ->assertSee('بدون سكر');

    $this->get(route('customer.store.orders.show', $otherOrder->payment_token))->assertNotFound();
    $this->get(route('customer.store.orders.show', $order->payment_token))->assertSessionHasNoErrors();
});

test('guests cannot access customer order history', function (): void {
    $this->get(route('customer.store.orders.index'))->assertRedirect();
});

test('paid receipt renders html and pdf while settings remain snapshotted', function (): void {
    $customer = Customer::factory()->create();
    $order = customerStoreOrder($customer);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.store.orders.receipt', $order->payment_token))
        ->assertSuccessful()
        ->assertSee('بيرحاء للتجارب')
        ->assertSee('OM123')
        ->assertSee($order->reference);

    $this->get(route('customer.store.orders.receipt.pdf', $order->payment_token))
        ->assertSuccessful()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertDownload('byruhaa-receipt-'.$order->reference.'.pdf');

    $settings = app(StoreSettings::class);
    $settings->legal_name = 'اسم جديد بعد البيع';
    $settings->save();

    $renderer = app(StoreReceiptRenderer::class);
    expect($renderer->settingsFor($order->refresh())['seller_legal_name'])->toBe('بيرحاء للتجارب');
});

test('refunded orders retain receipt access and show their refund status', function (): void {
    $customer = Customer::factory()->create();
    $order = customerStoreOrder($customer, ['status' => OrderStatus::Refunded->value]);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.store.orders.receipt', $order->payment_token))
        ->assertSuccessful()
        ->assertSee('تم استرداد المبلغ');
});

test('unpaid orders do not expose receipts', function (): void {
    $customer = Customer::factory()->create();
    $order = customerStoreOrder($customer, ['status' => OrderStatus::PendingPayment->value, 'paid_at' => null]);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.store.orders.receipt', $order->payment_token))
        ->assertNotFound();
});

test('reorder adds current purchasable options with current price and note without creating an order', function (): void {
    $customer = Customer::factory()->create();
    $product = Product::factory()->active()->create();
    $option = ProductOption::factory()->for($product)->default()->create(['price_baisa' => 2400]);
    $order = customerStoreOrder($customer);
    $item = $order->items()->firstOrFail();
    $item->forceFill([
        'product_option_id' => $option->id,
        'product_name' => $product->name,
        'option_name' => $option->name,
        'sku' => $option->sku,
        'quantity' => 2,
        'note' => 'حليب إضافي',
    ])->save();

    $beforeOrders = Order::query()->count();
    $result = app(ReorderStoreOrder::class)->execute($order, $customer->id);
    $cart = Cart::query()->where('customer_id', $customer->id)->firstOrFail();

    expect($result['added'])->toHaveCount(1)
        ->and($result['unavailable'])->toBeEmpty()
        ->and($cart->items()->firstOrFail()->quantity)->toBe(2)
        ->and($cart->items()->firstOrFail()->note)->toBe('حليب إضافي')
        ->and($cart->items()->firstOrFail()->productOption->price_baisa)->toBe(2400)
        ->and(Order::query()->count())->toBe($beforeOrders);
});

test('fully unavailable reorder does not create or modify a cart', function (): void {
    $customer = Customer::factory()->create();
    $order = customerStoreOrder($customer);
    $option = ProductOption::query()->firstOrFail();
    $option->update(['is_available' => false]);
    $order->items()->firstOrFail()->update(['product_option_id' => $option->id]);

    $result = app(ReorderStoreOrder::class)->execute($order, $customer->id);

    expect($result['added'])->toBeEmpty()
        ->and($result['unavailable'])->not->toBeEmpty()
        ->and(Cart::query()->where('customer_id', $customer->id)->count())->toBe(0);
});
