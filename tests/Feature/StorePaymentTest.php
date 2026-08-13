<?php

use App\Enums\PaymentState;
use App\Enums\ThawaniWebhookEventStatus;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\PaymentRefund;
use App\Modules\Identity\Models\Customer;
use App\Modules\Store\Actions\AddCartItem;
use App\Modules\Store\Actions\ChangeOrderState;
use App\Modules\Store\Actions\CreateOrder;
use App\Modules\Store\Actions\ExpirePendingOrders;
use App\Modules\Store\Enums\InventoryReservationStatus;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Settings\StoreSettings;
use App\Modules\Store\States\Order\Expired;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

beforeEach(function (): void {
    config([
        'payments.default' => 'thawani',
        'thawani.mode' => 'test',
        'thawani.test.secret_key' => 'test_secret_key',
        'thawani.test.publishable_key' => 'test_publishable_key',
        'thawani.test.base_url' => 'https://uatcheckout.thawani.om/api/v1',
        'thawani.test.checkout_base_url' => 'https://uatcheckout.thawani.om/pay',
    ]);

    $settings = app(StoreSettings::class);
    $settings->ordering_enabled = true;
    $settings->save();
});

function storePaymentOrder(bool $tracksInventory = true): Order
{
    $product = Product::factory()->create(['status' => 'active']);
    $option = $product->defaultOption()->firstOrFail();
    $option->forceFill([
        'name' => 'Standard',
        'sku' => 'PAY-'.fake()->unique()->numerify('#####'),
        'price_baisa' => 1000,
        'is_available' => true,
        'tracks_inventory' => $tracksInventory,
        'stock_on_hand' => $tracksInventory ? 3 : 0,
    ])->save();

    $cart = Cart::factory()->create();
    app(AddCartItem::class)->execute($cart, $option, 1);

    return app(CreateOrder::class)->execute($cart, [
        'idempotency_key' => 'payment-'.fake()->unique()->numerify('#####'),
        'customer_name' => 'Mona Said',
        'customer_phone' => '+96891234567',
        'customer_email' => 'mona@example.com',
        'pickup_type' => 'immediate',
    ]);
}

function fakeStoreCheckout(string $sessionId = 'store_checkout_123', string $status = 'paid', ?int $amount = null): void
{
    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/checkout/session' => Http::response([
            'success' => true,
            'data' => ['session_id' => $sessionId, 'invoice' => 'INV-'.$sessionId],
        ]),
        "https://uatcheckout.thawani.om/api/v1/checkout/session/{$sessionId}" => function () use ($sessionId, $status, $amount) {
            $payment = Payment::query()->latest('id')->first();

            return Http::response([
                'success' => true,
                'data' => [
                    'session_id' => $sessionId,
                    'client_reference_id' => $payment?->reference,
                    'payment_status' => $status,
                    'total_amount' => $amount ?? 1050,
                    'currency' => 'OMR',
                    'payment_id' => 'PAYMENT-'.$sessionId,
                    'invoice' => 'INV-'.$sessionId,
                ],
            ]);
        },
        "https://uatcheckout.thawani.om/api/v1/checkout/{$sessionId}/cancel" => Http::response(['success' => true]),
        'https://uatcheckout.thawani.om/api/v1/refunds' => Http::response([
            'success' => true,
            'data' => ['refund_id' => 'REFUND-'.$sessionId, 'status' => 'succeeded'],
        ]),
    ]);
}

test('store order payment is initiated idempotently and paid return confirms the order once', function (): void {
    Http::preventStrayRequests();
    fakeStoreCheckout();
    $order = storePaymentOrder();

    $first = $this->postJson(route('store.orders.payment.store', ['order' => $order->payment_token]));
    $second = $this->postJson(route('store.orders.payment.store', ['order' => $order->payment_token]));

    $first->assertCreated()->assertJsonPath('checkout_url', 'https://uatcheckout.thawani.om/pay/store_checkout_123?key=test_publishable_key');
    $second->assertCreated()->assertJsonPath('payment_reference', $first->json('payment_reference'));
    expect(Payment::query()->where('subject_reference', $order->reference)->count())->toBe(1);
    Http::assertSentCount(1);

    $success = $this->getJson(URL::signedRoute('store.orders.payment.success', ['order' => $order->payment_token]));
    $success->assertOk()->assertJsonPath('status', 'paid');

    $order->refresh();
    expect($order->status->getValue())->toBe('confirmed')
        ->and($order->paid_at)->not->toBeNull()
        ->and($order->payment_reference)->not->toBeNull()
        ->and($order->provider_invoice)->toBe('INV-store_checkout_123')
        ->and($order->inventoryReservation->reservation->fresh()->status)->toBe(InventoryReservationStatus::Consumed)
        ->and($order->statusHistory()->where('to_status', 'confirmed')->count())->toBe(1)
        ->and($order->inventoryReservation->reservation->items()->count())->toBe(1);

    $this->getJson(URL::signedRoute('store.orders.payment.success', ['order' => $order->payment_token]))->assertOk();
    expect($order->refresh()->statusHistory()->where('to_status', 'confirmed')->count())->toBe(1);
});

test('browser payment returns render a signed human-readable order status page', function (): void {
    $order = storePaymentOrder();

    $response = $this->get(URL::signedRoute('store.orders.status', ['order' => $order->payment_token]));

    $response->assertSuccessful()
        ->assertSee($order->reference)
        ->assertSee('wire:id=', false);
});

test('paid verification rejects a mismatched amount without confirming the order', function (): void {
    Http::preventStrayRequests();
    $order = storePaymentOrder();
    fakeStoreCheckout(amount: 9999, status: 'paid');
    $this->postJson(route('store.orders.payment.store', ['order' => $order->payment_token]))->assertCreated();

    $this->getJson(URL::signedRoute('store.orders.payment.success', ['order' => $order->payment_token]))->assertStatus(422);

    expect($order->refresh()->status->getValue())->toBe('pending_payment')
        ->and($order->inventoryReservation->reservation->fresh()->status)->toBe(InventoryReservationStatus::Pending)
        ->and(Payment::query()->where('subject_reference', $order->reference)->firstOrFail()->state)->toBe(PaymentState::Failed);
});

test('expired orders with a late paid session are refunded without consuming inventory', function (): void {
    Http::preventStrayRequests();
    $order = storePaymentOrder();
    fakeStoreCheckout(status: 'paid');
    $this->postJson(route('store.orders.payment.store', ['order' => $order->payment_token]))->assertCreated();

    app(ChangeOrderState::class)->execute($order, Expired::class);
    $this->getJson(URL::signedRoute('store.orders.payment.success', ['order' => $order->payment_token]))->assertOk();

    expect($order->refresh()->status->getValue())->toBe('refunded')
        ->and($order->inventoryReservation->reservation->fresh()->status)->toBe(InventoryReservationStatus::Pending)
        ->and(PaymentRefund::query()->count())->toBe(1);
});

test('expiration verifies and cancels pending provider sessions before releasing stock', function (): void {
    Http::preventStrayRequests();
    $order = storePaymentOrder();
    fakeStoreCheckout(status: 'unpaid');
    $this->postJson(route('store.orders.payment.store', ['order' => $order->payment_token]))->assertCreated();
    $order->forceFill(['created_at' => now()->subMinutes(3)])->save();

    expect(app(ExpirePendingOrders::class)->execute())->toBe(1)
        ->and($order->refresh()->status->getValue())->toBe('expired')
        ->and($order->inventoryReservation->reservation->fresh()->status)->toBeIn([InventoryReservationStatus::Released, InventoryReservationStatus::Expired])
        ->and(Payment::query()->where('subject_reference', $order->reference)->firstOrFail()->state)->toBe(PaymentState::Cancelled);

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/cancel'));
});

test('the existing thawani webhook route confirms store orders without entering the booking flow', function (): void {
    Http::preventStrayRequests();
    $order = storePaymentOrder();
    fakeStoreCheckout(status: 'paid');
    $this->postJson(route('store.orders.payment.store', ['order' => $order->payment_token]))->assertCreated();
    $payment = Payment::query()->where('subject_reference', $order->reference)->firstOrFail();

    $payload = ['client_reference_id' => $payment->reference, 'event' => 'checkout.paid'];
    $this->postJson(route('api.webhooks.thawani'), $payload)
        ->assertOk()
        ->assertJsonPath('status', ThawaniWebhookEventStatus::Processed->value);
    $this->postJson(route('api.webhooks.thawani'), $payload)->assertOk();

    expect($order->refresh()->status->getValue())->toBe('confirmed')
        ->and($order->inventoryReservation->reservation->fresh()->status)->toBe(InventoryReservationStatus::Consumed)
        ->and($order->statusHistory()->where('to_status', 'confirmed')->count())->toBe(1)
        ->and($payment->refresh()->state)->toBe(PaymentState::Paid);
});

test('customer-owned store orders require the matching customer for payment initiation', function (): void {
    $customer = Customer::factory()->create();
    $order = storePaymentOrder();
    $order->forceFill(['customer_id' => $customer->id])->save();

    expect($this->postJson(route('store.orders.payment.store', ['order' => $order->payment_token]))->status())->toBe(422);
});

test('a thawani initiation failure leaves a failed payment record and does not expose gateway details', function (): void {
    Http::preventStrayRequests();
    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/checkout/session' => Http::response([
            'success' => false,
            'description' => 'Temporary provider error',
        ], 503),
    ]);
    $order = storePaymentOrder();

    $response = $this->postJson(route('store.orders.payment.store', ['order' => $order->payment_token]));

    $response->assertStatus(503)->assertJson(['message' => 'The payment provider is temporarily unavailable.']);
    expect(Payment::query()->where('subject_reference', $order->reference)->firstOrFail()->state)->toBe(PaymentState::Failed);
});
