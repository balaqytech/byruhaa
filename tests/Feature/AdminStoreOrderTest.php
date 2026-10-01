<?php

use App\Modules\Finance\Contracts\WalletService;
use App\Modules\Finance\Models\WalletTopUp;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Identity\Models\User;
use App\Modules\Store\Actions\CreateAdminOrder;
use App\Modules\Store\Filament\Resources\Orders\OrderResource;
use App\Modules\Store\Filament\Resources\Orders\Pages\CreateOrder;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Settings\StoreSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function (): void {
    Queue::fake();
    config(['byruhaa.wallets.enabled' => true]);

    $settings = app(StoreSettings::class);
    $settings->ordering_enabled = true;
    $settings->save();

    $this->customer = Customer::factory()->create();
    $this->minor = MinorProfile::factory()
        ->for(FamilyMember::factory()->for($this->customer))
        ->create(['wallet_spending_enabled' => true]);
    $this->wallet = app(WalletService::class)->walletForMinorProfile($this->minor->id);
    $this->wallet->increment('balance_baisa', 5000);
    WalletTopUp::query()->create([
        'wallet_id' => $this->wallet->id,
        'operation_key' => 'admin-order-top-up',
        'status' => 'credited',
        'currency' => 'OMR',
        'amount_baisa' => 5000,
        'spendable_baisa' => 5000,
        'refundable_baisa' => 5000,
        'credited_at' => now(),
        'refund_deadline_at' => now()->addDay(),
    ]);

    $product = Product::factory()->active()->create(['name' => 'Admin order coffee']);
    $this->option = $product->defaultOption()->firstOrFail();
    $this->option->update([
        'price_baisa' => 1000,
        'member_price_baisa' => 750,
        'is_available' => true,
        'tracks_inventory' => true,
        'stock_on_hand' => 5,
    ]);
});

function adminOrderData(int $customerId, int $optionId, ?int $minorProfileId = null, string $paymentMethod = 'thawani'): array
{
    return [
        'customer_id' => $customerId,
        'minor_profile_id' => $minorProfileId,
        'payment_method' => $paymentMethod,
        'pickup_type' => 'immediate',
        'items' => [['product_option_id' => $optionId, 'quantity' => 2]],
    ];
}

test('admin can create a customer order waiting for direct payment and share a signed link', function (): void {
    $this->actingAs(User::factory()->create(), 'web');

    $this->get(OrderResource::getUrl('create'))->assertOk();

    Livewire::test(CreateOrder::class)
        ->fillForm(adminOrderData($this->customer->id, $this->option->id))
        ->call('create')
        ->assertHasNoFormErrors();

    $order = Order::query()->sole();
    $paymentUrl = URL::temporarySignedRoute(
        'store.orders.payment.link',
        $order->created_at->copy()->addMinutes(app(StoreSettings::class)->reservation_duration_minutes),
        ['order' => $order->payment_token],
    );

    expect($order->status->getValue())->toBe('pending_payment')
        ->and($order->payment_method)->toBe('thawani')
        ->and($order->minor_profile_id)->toBeNull()
        ->and($order->total_baisa)->toBe(1500)
        ->and($order->paid_at)->toBeNull()
        ->and($this->wallet->refresh()->balance_baisa)->toBe(5000)
        ->and(Cart::query()->count())->toBe(0)
        ->and(URL::hasValidSignature(Request::create($paymentUrl)))->toBeTrue();

    $this->get(OrderResource::getUrl('view', ['record' => $order]))
        ->assertOk()
        ->assertSee(__('admin.store.admin_order.payment_link'))
        ->assertSee('store/orders/'.$order->payment_token.'/payment');
});

test('admin minor wallet order charges the member price once and confirms immediately', function (): void {
    $data = adminOrderData($this->customer->id, $this->option->id, $this->minor->id, 'wallet');

    $order = app(CreateAdminOrder::class)->execute($data, 'admin-minor-wallet-1');
    $retried = app(CreateAdminOrder::class)->execute($data, 'admin-minor-wallet-1');

    expect($order->id)->toBe($retried->id)
        ->and($order->refresh()->status->getValue())->toBe('confirmed')
        ->and($order->pricing_tier)->toBe('member')
        ->and($order->total_baisa)->toBe(1500)
        ->and($order->minor_profile_id)->toBe($this->minor->id)
        ->and($this->wallet->refresh()->balance_baisa)->toBe(3500)
        ->and($this->wallet->movements()->where('type', 'purchase')->count())->toBe(1)
        ->and($this->option->refresh()->stock_on_hand)->toBe(3)
        ->and(Cart::query()->count())->toBe(0);
});

test('admin can create a minor wallet order through the Filament form', function (): void {
    $this->actingAs(User::factory()->create(), 'web');

    Livewire::test(CreateOrder::class)
        ->fillForm(adminOrderData($this->customer->id, $this->option->id, $this->minor->id, 'wallet'))
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Order::query()->sole()->status->getValue())->toBe('confirmed')
        ->and($this->wallet->refresh()->balance_baisa)->toBe(3500);
});

test('admin may create a minor direct-payment order without charging its wallet', function (): void {
    $order = app(CreateAdminOrder::class)->execute(
        adminOrderData($this->customer->id, $this->option->id, $this->minor->id),
        'admin-minor-direct-1',
    );

    expect($order->status->getValue())->toBe('pending_payment')
        ->and($order->pricing_tier)->toBe('member')
        ->and($order->total_baisa)->toBe(1500)
        ->and($this->wallet->refresh()->balance_baisa)->toBe(5000);
});

test('admin order rejects a customer wallet payment or a minor belonging to another guardian', function (): void {
    expect(fn () => app(CreateAdminOrder::class)->execute(
        adminOrderData($this->customer->id, $this->option->id, null, 'wallet'),
        'invalid-customer-wallet',
    ))->toThrow(ValidationException::class);

    $otherCustomer = Customer::factory()->create();

    expect(fn () => app(CreateAdminOrder::class)->execute(
        adminOrderData($otherCustomer->id, $this->option->id, $this->minor->id, 'wallet'),
        'invalid-minor-guardian',
    ))->toThrow(ValidationException::class)
        ->and(Order::query()->count())->toBe(0)
        ->and($this->wallet->refresh()->balance_baisa)->toBe(5000);
});

test('admin wallet order rolls back the order and inventory when funds are insufficient', function (): void {
    $this->wallet->update(['balance_baisa' => 0]);

    expect(fn () => app(CreateAdminOrder::class)->execute(
        adminOrderData($this->customer->id, $this->option->id, $this->minor->id, 'wallet'),
        'admin-insufficient-wallet',
    ))->toThrow(ValidationException::class)
        ->and(Order::query()->count())->toBe(0)
        ->and(Cart::query()->count())->toBe(0)
        ->and($this->option->refresh()->stock_on_hand)->toBe(5);
});

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
