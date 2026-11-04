<?php

use App\Modules\Finance\Contracts\WalletService;
use App\Modules\Finance\Models\WalletTopUp;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Store\Actions\CreateAdminOrder;
use App\Modules\Store\Filament\Resources\Orders\OrderResource;
use App\Modules\Store\Filament\Resources\Orders\Pages\CreateGuestOrder;
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
use Mockery\MockInterface;
use Spatie\Permission\Models\Permission;

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

    $data = adminOrderData($this->customer->id, $this->option->id);
    $data['items'][0]['note'] = 'بدون سكر';

    Livewire::test(CreateOrder::class)
        ->fillForm($data)
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
        ->and($order->items()->sole()->note)->toBe('بدون سكر')
        ->and($order->paid_at)->toBeNull()
        ->and($this->wallet->refresh()->balance_baisa)->toBe(5000)
        ->and(Cart::query()->count())->toBe(0)
        ->and(URL::hasValidSignature(Request::create($paymentUrl)))->toBeTrue();

    $this->get(OrderResource::getUrl('view', ['record' => $order]))
        ->assertOk()
        ->assertSee(__('admin.store.admin_order.payment_link'))
        ->assertSee('store/orders/'.$order->payment_token.'/payment');
});

test('admin can create a guest order without a customer account', function (): void {
    $this->actingAs(User::factory()->create(), 'web');

    $this->get(OrderResource::getUrl('create-guest'))
        ->assertSuccessful()
        ->assertSee(__('admin.store.admin_order.guest_details'));

    Livewire::test(CreateGuestOrder::class)
        ->fillForm([
            'customer_name' => 'Walk-in Guest',
            'customer_phone' => '91234567',
            'customer_email' => 'guest@example.com',
            'pickup_type' => 'immediate',
            'items' => [['product_option_id' => $this->option->id, 'quantity' => 2]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $order = Order::query()->sole();

    expect($order->customer_id)->toBeNull()
        ->and($order->minor_profile_id)->toBeNull()
        ->and($order->customer_name)->toBe('Walk-in Guest')
        ->and($order->customer_phone)->toBe('+96891234567')
        ->and($order->customer_email)->toBe('guest@example.com')
        ->and($order->payment_method)->toBe('thawani')
        ->and($order->status->getValue())->toBe('pending_payment')
        ->and($order->total_baisa)->toBe(2000)
        ->and(Customer::query()->count())->toBe(1)
        ->and(Cart::query()->count())->toBe(0);

    $this->get(OrderResource::getUrl('view', ['record' => $order]))
        ->assertSuccessful()
        ->assertSee(__('admin.store.admin_order.payment_link'));
});

test('guest order cannot use a customer account or a minor wallet', function (): void {
    $guestData = [
        'customer_name' => 'Walk-in Guest',
        'customer_phone' => '91234567',
        'pickup_type' => 'immediate',
        'items' => [['product_option_id' => $this->option->id, 'quantity' => 1]],
    ];

    expect(fn () => app(CreateAdminOrder::class)->executeGuest([
        ...$guestData,
        'customer_id' => $this->customer->id,
    ], 'invalid-guest-customer'))->toThrow(ValidationException::class);

    expect(fn () => app(CreateAdminOrder::class)->executeGuest([
        ...$guestData,
        'minor_profile_id' => $this->minor->id,
    ], 'invalid-guest-minor'))->toThrow(ValidationException::class)
        ->and(Order::query()->count())->toBe(0);
});

test('staff without order creation permission cannot access the guest order page', function (): void {
    $staff = User::factory()->create();
    $role = Role::findOrCreate('store_order_viewer', 'web');
    $role->givePermissionTo(Permission::findOrCreate('ViewAny:Order', 'web'));
    $staff->syncRoles($role);

    $this->actingAs($staff, 'web')
        ->get(OrderResource::getUrl('create-guest'))
        ->assertForbidden();
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

test('admin order wizard shows an Arabic notification for insufficient wallet balance', function (): void {
    $this->actingAs(User::factory()->create(), 'web');
    $this->wallet->update(['balance_baisa' => 0]);
    app()->setLocale('ar');

    Livewire::test(CreateOrder::class)
        ->fillForm(adminOrderData($this->customer->id, $this->option->id, $this->minor->id, 'wallet'))
        ->call('create')
        ->assertHasFormErrors(['payment_method'])
        ->assertNotified(__('admin.store.admin_order.wallet_insufficient'));

    expect(Order::query()->count())->toBe(0);
});

test('admin order wizard shows a failure notification when saving throws an unexpected error', function (): void {
    $this->actingAs(User::factory()->create(), 'web');
    app()->setLocale('ar');

    $this->mock(CreateAdminOrder::class, function (MockInterface $mock): void {
        $mock->shouldReceive('execute')->once()->andThrow(new RuntimeException('Simulated storage failure'));
    });

    Livewire::test(CreateOrder::class)
        ->fillForm(adminOrderData($this->customer->id, $this->option->id, $this->minor->id, 'wallet'))
        ->call('create')
        ->assertNotified(__('admin.store.admin_order.creation_failed'));

    expect(Order::query()->count())->toBe(0)
        ->and($this->wallet->refresh()->balance_baisa)->toBe(5000);
});

test('admin item notes are saved with the order items', function (): void {
    $data = adminOrderData($this->customer->id, $this->option->id);
    $data['items'][0]['note'] = 'بدون سكر';

    $order = app(CreateAdminOrder::class)->execute($data, 'admin-item-note');

    expect($order->items()->sole()->note)->toBe('بدون سكر');
});

test('example', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
});
