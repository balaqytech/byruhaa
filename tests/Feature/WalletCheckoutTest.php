<?php

use App\Livewire\Store\Checkout;
use App\Modules\Finance\Contracts\WalletService;
use App\Modules\Finance\Models\WalletTopUp;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Store\Actions\AddCartItem;
use App\Modules\Store\Actions\ConfirmWalletOrder;
use App\Modules\Store\Actions\CreateOrder;
use App\Modules\Store\Enums\InventoryReservationStatus;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\InventoryReservation;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Settings\StoreSettings;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function (): void {
    Queue::fake();
    config(['byruhaa.wallets.enabled' => true]);
    $settings = app(StoreSettings::class);
    $settings->ordering_enabled = true;
    $settings->save();
    $this->guardian = Customer::factory()->create(['phone_verified_at' => null]);
    $this->profile = MinorProfile::factory()->for(FamilyMember::factory()->for($this->guardian))
        ->create(['wallet_spending_enabled' => true]);
    $this->wallet = app(WalletService::class)->walletForMinorProfile($this->profile->id);
    $this->wallet->increment('balance_baisa', 5000);
    WalletTopUp::query()->create([
        'wallet_id' => $this->wallet->id,
        'operation_key' => 'checkout-top-up',
        'status' => 'credited',
        'currency' => 'OMR',
        'amount_baisa' => 5000,
        'spendable_baisa' => 5000,
        'refundable_baisa' => 5000,
        'credited_at' => now(),
        'refund_deadline_at' => now()->addDay(),
    ]);
    $product = Product::factory()->active()->create(['name' => 'Wallet Checkout Coffee']);
    $this->option = $product->defaultOption()->firstOrFail();
    $this->option->update(['price_baisa' => 1000, 'is_available' => true, 'tracks_inventory' => true, 'stock_on_hand' => 3]);
    $this->cart = Cart::factory()->create(['customer_id' => $this->guardian->id, 'minor_profile_id' => $this->profile->id]);
    app(AddCartItem::class)->execute($this->cart, $this->option, 1);
    session()->put('store_cart_token', $this->cart->token);
    $this->actingAs($this->profile, 'minor-profile');
});

test('minor wallet checkout confirms orders with or without tracked stock exactly once', function (bool $tracksInventory): void {
    $this->option->update(['tracks_inventory' => $tracksInventory]);
    $component = Livewire::test(Checkout::class)->assertSet('walletPaymentAvailable', true)
        ->set('paymentMethod', 'wallet')->call('placeOrder')->assertHasNoErrors();
    $order = Order::query()->sole();
    $component->assertRedirect(route('minor.orders.show', $order->payment_token))->assertDispatched('store-cart-updated');
    app(ConfirmWalletOrder::class)->execute($order, $this->guardian->id, $this->profile->id);
    expect($order->refresh()->status->getValue())->toBe('confirmed')
        ->and($order->paid_at)->not->toBeNull()
        ->and($this->wallet->refresh()->balance_baisa)->toBe(5000 - $order->total_baisa)
        ->and($this->wallet->movements()->where('type', 'purchase')->count())->toBe(1)
        ->and($this->cart->items()->count())->toBe(0)
        ->and($this->option->refresh()->stock_on_hand)->toBe($tracksInventory ? 2 : 3);
    if ($tracksInventory) {
        expect($order->inventoryReservation->reservation->status)->toBe(InventoryReservationStatus::Consumed);
    } else {
        expect($order->inventoryReservation)->toBeNull();
    }
})->with([true, false]);

test('minor wallet checkout debits the saved member price', function (): void {
    $this->option->update(['member_price_baisa' => 750]);

    Livewire::test(Checkout::class)
        ->set('paymentMethod', 'wallet')
        ->call('placeOrder')
        ->assertHasNoErrors();

    $order = Order::query()->sole();
    app(ConfirmWalletOrder::class)->execute($order, $this->guardian->id, $this->profile->id);

    expect($order->refresh())
        ->pricing_tier->toBe('member')
        ->regular_total_baisa->toBe(1000)
        ->discount_baisa->toBe(250)
        ->total_baisa->toBe(750)
        ->and($this->wallet->refresh()->balance_baisa)->toBe(4250);
});

test('legacy phone verification configuration cannot block wallet checkout', function (): void {
    config(['byruhaa.phone_verification.required' => true]);
    Livewire::test(Checkout::class)->assertSet('walletPaymentAvailable', true)
        ->set('paymentMethod', 'wallet')->call('placeOrder')->assertHasNoErrors();

    expect($this->guardian->refresh()->phone_verified_at)->toBeNull()
        ->and(Order::query()->count())->toBe(1);
});

test('insufficient wallet checkout keeps the cart and rolls back inventory so it can be retried', function (): void {
    $this->wallet->update(['balance_baisa' => 0]);
    $component = Livewire::test(Checkout::class)->set('paymentMethod', 'wallet')->call('placeOrder')
        ->assertHasErrors()->assertNoRedirect()->assertSee('Wallet Checkout Coffee')->assertDontSee('السلة فارغة');
    expect(Order::query()->count())->toBe(0)
        ->and(InventoryReservation::query()->count())->toBe(0)
        ->and($this->cart->items()->count())->toBe(1)
        ->and($this->option->refresh()->stock_on_hand)->toBe(3)
        ->and($this->wallet->movements()->count())->toBe(0);
    $this->wallet->update(['balance_baisa' => 5000]);
    $component->call('placeOrder')->assertHasNoErrors();
    expect(Order::query()->sole()->status->getValue())->toBe('confirmed');
});

test('minor can retry an existing wallet order without an inventory reservation', function (): void {
    $this->option->update(['tracks_inventory' => false]);
    $order = app(CreateOrder::class)->execute($this->cart, [
        'idempotency_key' => 'existing-wallet-order',
        'customer_id' => $this->guardian->id,
        'minor_profile_id' => $this->profile->id,
        'customer_name' => $this->guardian->name,
        'customer_phone' => $this->guardian->phone_number,
        'pickup_type' => 'immediate',
        'payment_method' => 'wallet',
    ]);
    $this->post(route('minor.orders.payment', $order->payment_token))->assertSessionHasNoErrors()
        ->assertRedirect(route('minor.orders.show', $order->payment_token));
    expect($order->refresh()->status->getValue())->toBe('confirmed');
});

test('wallet payment still rejects expired or released inventory without debiting the wallet', function (string $status): void {
    $order = app(CreateOrder::class)->execute($this->cart, [
        'idempotency_key' => 'unavailable-wallet-order',
        'customer_id' => $this->guardian->id,
        'minor_profile_id' => $this->profile->id,
        'customer_name' => $this->guardian->name,
        'customer_phone' => $this->guardian->phone_number,
        'pickup_type' => 'immediate',
        'payment_method' => 'wallet',
    ]);
    $order->inventoryReservation->reservation->update([
        'status' => $status,
        'expires_at' => $status === 'pending' ? now()->subMinute() : now()->addMinutes(10),
    ]);
    expect(fn () => app(ConfirmWalletOrder::class)->execute($order, $this->guardian->id, $this->profile->id))
        ->toThrow(ValidationException::class);
    expect($this->wallet->refresh()->balance_baisa)->toBe(5000)
        ->and($this->wallet->movements()->count())->toBe(0)
        ->and($this->option->refresh()->stock_on_hand)->toBe(3)
        ->and($order->refresh()->paid_at)->toBeNull();
})->with(['pending', 'released', 'expired']);
