<?php

use App\Jobs\UchatWebhookJob;
use App\Models\WebhookDelivery;
use App\Modules\Identity\Models\Customer;
use App\Modules\Store\Actions\ChangeOrderState;
use App\Modules\Store\Models\Category;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Models\ProductOption;
use App\Modules\Store\Settings\StoreSettings;
use App\Modules\Store\States\Order\Expired;
use App\Services\Webhooks\ByruhaaWebhookSender;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

function uchatHeaders(array $headers = []): array
{
    return array_merge([
        'Authorization' => 'Bearer uchat-secret',
        'X-WhatsApp-Phone' => '91234567',
    ], $headers);
}

function enableUchatStore(): void
{
    config([
        'thawani.mode' => 'test',
        'thawani.test.secret_key' => 'test_secret_key',
        'thawani.test.publishable_key' => 'test_publishable_key',
        'thawani.test.base_url' => 'https://uatcheckout.thawani.om/api/v1',
        'thawani.test.checkout_base_url' => 'https://uatcheckout.thawani.om/pay',
        'byruhaa.uchat.api_token' => 'uchat-secret',
        'byruhaa.uchat.owner_key_secret' => 'owner-secret',
        'byruhaa.uchat.webhook_url' => 'https://uchat.test/store',
        'byruhaa.uchat.webhook_bearer_token' => 'outbound-secret',
        'byruhaa.uchat.webhook_signing_secret' => 'signing-secret',
    ]);
    $settings = app(StoreSettings::class);
    $settings->ordering_enabled = true;
    $settings->save();
}

function uchatOption(array $attributes = []): ProductOption
{
    $category = Category::factory()->create();
    $product = Product::factory()->active()->for($category)->create();
    $option = $product->defaultOption()->firstOrFail();
    $option->forceFill(array_merge([
        'name' => '250g',
        'sku' => 'COFFEE-'.fake()->unique()->numerify('#####'),
        'price_baisa' => 1500,
        'is_available' => true,
    ], $attributes))->save();

    return $option->load('product.category');
}

test('UChat routes fail closed when the token is missing, invalid, or unconfigured', function (): void {
    $response = $this->getJson('/api/v1/integrations/uchat/store/catalog');
    $response->assertStatus(503)->assertJsonPath('code', 'uchat_not_configured');

    config(['byruhaa.uchat.api_token' => 'configured', 'byruhaa.uchat.owner_key_secret' => 'owner-secret']);
    $this->withHeaders(['Authorization' => 'Bearer wrong'])->getJson('/api/v1/integrations/uchat/store/catalog')->assertUnauthorized();
});

test('UChat routes fail closed when the owner-key secret is missing', function (): void {
    config(['byruhaa.uchat.api_token' => 'configured', 'byruhaa.uchat.owner_key_secret' => null]);

    $this->withHeaders(['Authorization' => 'Bearer configured'])
        ->getJson('/api/v1/integrations/uchat/store/catalog')
        ->assertStatus(503)
        ->assertJsonPath('code', 'uchat_not_configured');
});

test('UChat routes use a dedicated throttle', function (): void {
    enableUchatStore();
    config(['byruhaa.uchat.rate_limit' => 1]);
    $headers = uchatHeaders(['X-WhatsApp-Phone' => '97123456']);

    $this->withHeaders($headers)->getJson('/api/v1/integrations/uchat/store/catalog')->assertSuccessful();
    $this->withHeaders($headers)->getJson('/api/v1/integrations/uchat/store/catalog')->assertTooManyRequests();
});

test('UChat throttling cannot be bypassed by rotating WhatsApp headers', function (): void {
    enableUchatStore();
    config(['byruhaa.uchat.rate_limit' => 1]);

    $this->withHeaders(uchatHeaders(['X-WhatsApp-Phone' => '97123456']))
        ->getJson('/api/v1/integrations/uchat/store/catalog')
        ->assertSuccessful();

    $this->withHeaders(uchatHeaders(['X-WhatsApp-Phone' => '97999999']))
        ->getJson('/api/v1/integrations/uchat/store/catalog')
        ->assertTooManyRequests();
});

test('UChat catalog exposes only published products and options', function (): void {
    enableUchatStore();
    $available = uchatOption();
    $hiddenCategory = Category::factory()->inactive()->create();
    $hidden = Product::factory()->active()->for($hiddenCategory)->create();

    $response = $this->withHeaders(uchatHeaders())->getJson('/api/v1/integrations/uchat/store/catalog');

    $response->assertSuccessful()
        ->assertJsonPath('data.0.products.0.options.0.sku', $available->sku)
        ->assertJsonMissing(['slug' => $hidden->slug]);
});

test('UChat cart is phone-owned and quotes from server prices', function (): void {
    enableUchatStore();
    $option = uchatOption(['price_baisa' => 2300]);

    $response = $this->withHeaders(uchatHeaders())->postJson('/api/v1/integrations/uchat/store/cart/items', [
        'sku' => $option->sku,
        'quantity' => 2,
        'note' => 'No sugar',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.quote.subtotal_baisa', 4381)
        ->assertJsonPath('data.items.0.sku', $option->sku);

    $this->withHeaders(uchatHeaders(['X-WhatsApp-Phone' => '92345678']))
        ->getJson('/api/v1/integrations/uchat/store/cart')
        ->assertSuccessful()
        ->assertJsonPath('data.items', []);
});

test('UChat creates an idempotent order and initiates payment without trusting client prices', function (): void {
    enableUchatStore();
    $option = uchatOption(['price_baisa' => 2000]);
    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/checkout/session' => Http::response([
            'success' => true,
            'data' => ['session_id' => 'uchat_checkout_session'],
        ]),
    ]);
    $this->withHeaders(uchatHeaders())->postJson('/api/v1/integrations/uchat/store/cart/items', ['sku' => $option->sku, 'quantity' => 1])->assertSuccessful();

    $payload = ['customer_name' => 'Mona', 'pickup_type' => 'immediate', 'total_baisa' => 1];
    $headers = uchatHeaders(['Idempotency-Key' => 'uchat-checkout-1']);
    $first = $this->withHeaders($headers)->postJson('/api/v1/integrations/uchat/store/orders', $payload);
    $first->assertCreated()->assertJsonStructure(['data' => ['reference', 'total_baisa'], 'payment' => ['checkout_url']]);

    $second = $this->withHeaders($headers)->postJson('/api/v1/integrations/uchat/store/orders', $payload);
    $second->assertCreated()->assertJsonPath('data.reference', $first->json('data.reference'));

    expect(Order::query()->count())->toBe(1)
        ->and(Order::query()->sole()->total_baisa)->toBe(2000);
});

test('UChat does not expose missing Thawani credentials', function (): void {
    enableUchatStore();
    config([
        'thawani.test.secret_key' => null,
        'thawani.test.publishable_key' => null,
    ]);
    $option = uchatOption(['price_baisa' => 2000]);
    $this->withHeaders(uchatHeaders())
        ->postJson('/api/v1/integrations/uchat/store/cart/items', ['sku' => $option->sku, 'quantity' => 1])
        ->assertSuccessful();

    $response = $this->withHeaders(uchatHeaders(['Idempotency-Key' => 'uchat-missing-thawani']))
        ->postJson('/api/v1/integrations/uchat/store/orders', ['customer_name' => 'Mona', 'pickup_type' => 'immediate']);

    $response->assertStatus(503)
        ->assertJsonPath('code', 'payment_unavailable')
        ->assertJsonMissing(['secret_key' => null]);
});

test('UChat order history and details are restricted to the normalized phone', function (): void {
    enableUchatStore();
    $customer = Customer::factory()->create(['phone_number' => '+96891234567']);
    $order = Order::factory()->create(['customer_id' => $customer->id, 'customer_phone' => '+96891234567']);
    $other = Order::factory()->create(['customer_phone' => '+96892345678']);

    $this->withHeaders(uchatHeaders())->getJson('/api/v1/integrations/uchat/store/orders')->assertSuccessful()->assertJsonPath('meta.total', 1);
    $this->withHeaders(uchatHeaders())->getJson('/api/v1/integrations/uchat/store/orders/'.$order->reference)->assertSuccessful();
    $this->withHeaders(uchatHeaders())->getJson('/api/v1/integrations/uchat/store/orders/'.$other->reference)->assertNotFound();
});

test('store state transitions queue one signed UChat webhook per state', function (): void {
    enableUchatStore();
    Queue::fake();
    $order = Order::factory()->create();

    app(ChangeOrderState::class)->execute($order, Expired::class);
    Queue::assertPushed(UchatWebhookJob::class, 1);
    $delivery = WebhookDelivery::query()->where('event', 'store.order.expired')->sole();
    $queuedJob = null;
    Queue::assertPushed(UchatWebhookJob::class, function (UchatWebhookJob $job) use (&$queuedJob): bool {
        $queuedJob = $job;

        return true;
    });
    expect($delivery->payload['delivery_id'])->toBe($delivery->id)
        ->and($queuedJob?->headers['Authorization'])->toBe('Bearer outbound-secret')
        ->and($queuedJob?->payload['data']['order']['reference'])->toBe($order->reference)
        ->and($queuedJob?->payload)->not->toHaveKey('payment_token')
        ->and($queuedJob?->payload['data']['order'])->not->toHaveKey('id');
});

test('partially configured UChat webhooks fail closed without creating a delivery', function (): void {
    enableUchatStore();
    Queue::fake();
    config(['byruhaa.uchat.webhook_signing_secret' => null]);
    $order = Order::factory()->create();

    app(ByruhaaWebhookSender::class)->sendUchatOrderState($order);

    Queue::assertNothingPushed();
    expect(WebhookDelivery::query()->count())->toBe(0);
});

test('staging UChat webhooks require HTTPS', function (): void {
    enableUchatStore();
    Queue::fake();
    config([
        'app.env' => 'staging',
        'byruhaa.uchat.webhook_url' => 'http://uchat.test/store',
    ]);
    $order = Order::factory()->create();

    app(ByruhaaWebhookSender::class)->sendUchatOrderState($order);

    Queue::assertNothingPushed();
    expect(WebhookDelivery::query()->count())->toBe(0);
});

test('UChat state webhooks are not dispatched when the transition transaction rolls back', function (): void {
    enableUchatStore();
    Queue::fake();
    $order = Order::factory()->create();

    DB::beginTransaction();
    app(ChangeOrderState::class)->execute($order, Expired::class);
    DB::rollBack();

    Queue::assertNothingPushed();
    expect($order->refresh()->status->getValue())->toBe('pending_payment');
});
