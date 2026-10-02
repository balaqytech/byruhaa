<?php

use App\Modules\Store\Models\Order;

test('the public footer links to a private order tracking form', function (): void {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee(route('store.orders.track'))
        ->assertSee('تتبع طلبك');

    $this->get(route('store.orders.track'))
        ->assertSuccessful()
        ->assertSee('رقم الهاتف المرتبط بالطلب')
        ->assertSee('name="robots" content="noindex,nofollow"', false);
});

test('the matching reference and phone reveal only the order progress', function (): void {
    $order = Order::factory()->create([
        'customer_name' => 'Private Customer Name',
        'customer_phone' => '+96891234567',
        'status' => 'preparing',
        'pickup_type' => 'immediate',
    ]);

    $response = $this->post(route('store.orders.track.lookup'), [
        'reference' => strtolower($order->reference),
        'phone' => '91234567',
    ]);

    $response->assertSuccessful()
        ->assertSee($order->reference)
        ->assertSee('قيد التجهيز')
        ->assertSee('الخطوة التالية: جاهز للاستلام')
        ->assertSee('ثم: مكتمل')
        ->assertSee('value="2" max="4"', false)
        ->assertDontSee('Private Customer Name')
        ->assertDontSee($order->payment_token)
        ->assertDontSee('+96891234567');

    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

test('tracking never reveals an order for an incorrect reference or phone', function (): void {
    $order = Order::factory()->create(['customer_phone' => '+96891234567']);

    $this->post(route('store.orders.track.lookup'), [
        'reference' => $order->reference,
        'phone' => '92345678',
    ])->assertRedirect(route('store.orders.track'))
        ->assertSessionHasErrors('lookup');

    $this->post(route('store.orders.track.lookup'), [
        'reference' => 'BRH-ORD-UNKNOWN',
        'phone' => '+96891234567',
    ])->assertRedirect(route('store.orders.track'))
        ->assertSessionHasErrors('lookup');
});

test('public tracking attempts are rate limited', function (): void {
    $payload = ['reference' => 'BRH-ORD-UNKNOWN', 'phone' => '+96891234567'];
    $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.77']);

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('store.orders.track.lookup'), $payload)
            ->assertRedirect(route('store.orders.track'));
    }

    $this->post(route('store.orders.track.lookup'), $payload)
        ->assertTooManyRequests();
});

test('scheduled orders include acceptance and refunds show their own remaining step', function (): void {
    $scheduledOrder = Order::factory()->create([
        'customer_phone' => '+96891234567',
        'status' => 'accepted',
        'pickup_type' => 'scheduled',
    ]);

    $this->post(route('store.orders.track.lookup'), [
        'reference' => $scheduledOrder->reference,
        'phone' => '+96891234567',
    ])->assertSuccessful()
        ->assertSee('مقبول')
        ->assertSee('الخطوة التالية: قيد التجهيز');

    $refundOrder = Order::factory()->create([
        'customer_phone' => '+96891234567',
        'status' => 'refund_pending',
    ]);
    $refundOrder->statusHistory()->create([
        'from_status' => 'completed',
        'to_status' => 'refund_pending',
    ]);

    $this->post(route('store.orders.track.lookup'), [
        'reference' => $refundOrder->reference,
        'phone' => '+96891234567',
    ])->assertSuccessful()
        ->assertSee('مسار الاسترداد')
        ->assertSee('value="4" max="4"', false)
        ->assertSee('value="1" max="2"', false)
        ->assertSee('المتبقي: اكتمال عملية الاسترداد');
});
