<?php

use App\Filament\Resources\MinorProfiles\Pages\ListMinorProfiles;
use App\Modules\Finance\Contracts\WalletService;
use App\Modules\Finance\Events\WalletMovementPosted;
use App\Modules\Finance\Listeners\SendWalletMovementNotification;
use App\Modules\Finance\Models\WalletTopUp;
use App\Modules\Identity\Actions\ManageMinorPosCredential;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Pos\Actions\CreateCashPosOrder;
use App\Modules\Pos\Actions\CreatePosOrder;
use App\Modules\Pos\Livewire\BaristaBoard;
use App\Modules\Pos\Livewire\CashierOrders;
use App\Modules\Pos\Livewire\PickupBoard;
use App\Modules\Pos\Livewire\PosTerminal;
use App\Modules\Store\Actions\ChangeOrderState;
use App\Modules\Store\Events\OrderStateChanged;
use App\Modules\Store\Filament\Resources\Orders\OrderResource;
use App\Modules\Store\Listeners\SendUchatOrderStateWebhook;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\Product;
use App\Modules\Store\Settings\StoreSettings;
use App\Modules\Store\States\Order\Cancelled;
use App\Modules\Store\States\Order\Preparing;
use App\Modules\Store\States\Order\ReadyForPickup;
use App\Services\Webhooks\ByruhaaWebhookSender;
use Database\Seeders\StaffWorkspaceRolesSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function (): void {
    Queue::fake();
    config(['byruhaa.wallets.enabled' => true]);
    $settings = app(StoreSettings::class);
    $settings->ordering_enabled = true;
    $settings->save();

    $this->guardian = Customer::factory()->create();
    $this->minor = MinorProfile::factory()
        ->for(FamilyMember::factory()->for($this->guardian))
        ->create(['wallet_spending_enabled' => true]);
    $this->wallet = app(WalletService::class)->walletForMinorProfile($this->minor->id);
    $this->wallet->increment('balance_baisa', 5000);
    WalletTopUp::query()->create([
        'wallet_id' => $this->wallet->id,
        'operation_key' => 'pos-test-funds',
        'status' => 'credited',
        'currency' => 'OMR',
        'amount_baisa' => 5000,
        'spendable_baisa' => 5000,
        'refundable_baisa' => 5000,
        'credited_at' => now(),
        'refund_deadline_at' => now()->addDay(),
    ]);
    $product = Product::factory()->active()->create();
    $this->option = $product->defaultOption()->firstOrFail();
    $this->option->update([
        'price_baisa' => 1000,
        'member_price_baisa' => 750,
        'is_available' => true,
        'tracks_inventory' => true,
        'stock_on_hand' => 5,
    ]);

    $this->cashier = User::factory()->create();
    $permission = Permission::findOrCreate('Sell:Pos', 'web');
    $cashierRole = Role::findOrCreate('pos_cashier', 'web');
    $cashierRole->givePermissionTo($permission);
    $this->cashier->syncRoles($cashierRole);

    $credentials = app(ManageMinorPosCredential::class);
    $this->token = $credentials->issue($this->minor);
});

function posItems(int $optionId, int $quantity = 2): array
{
    return [['product_option_id' => $optionId, 'quantity' => $quantity]];
}

test('staff workspace roles are seeded with separate viewing and preparation permissions', function (): void {
    $this->seed(StaffWorkspaceRolesSeeder::class);

    expect(Role::findByName('pos_cashier', 'web')->hasPermissionTo('Sell:Pos'))->toBeTrue()
        ->and(Role::findByName('pos_cashier', 'web')->hasPermissionTo('Reprint:PosReceipts'))->toBeTrue()
        ->and(Role::findByName('pos_barista', 'web')->hasPermissionTo('View:BaristaBoard'))->toBeTrue()
        ->and(Role::findByName('pos_barista', 'web')->hasPermissionTo('Prepare:BaristaOrders'))->toBeTrue()
        ->and(Role::findByName('pos_pickup_attendant', 'web')->hasPermissionTo('View:PickupBoard'))->toBeTrue()
        ->and(Role::findByName('pos_pickup_attendant', 'web')->hasPermissionTo('Complete:PickupOrders'))->toBeTrue()
        ->and(Permission::findByName('Manage:PosCards', 'web')->exists)->toBeTrue();
});

test('only an authorized cashier can open the terminal and charge a wallet', function (): void {
    $outsider = User::factory()->create();
    $outsider->syncRoles(Role::findOrCreate('unrelated_staff', 'web'));

    $this->get(route('cashier.terminal'))->assertRedirect(route('cashier.login'));
    $this->actingAs($outsider, 'cashier')->get(route('cashier.terminal'))->assertForbidden();
    expect(fn () => app(CreatePosOrder::class)->execute($outsider, $this->token, posItems($this->option->id), 1500, 'denied', $this->minor->id))
        ->toThrow(HttpException::class);

    $this->actingAs($this->cashier, 'cashier')->get(route('cashier.terminal'))->assertSuccessful();
    Livewire::test('store.pos-terminal')->assertSee('ابدأ طلبًا جديدًا');
    $this->actingAs($this->cashier, 'web')->get('/admin')->assertForbidden();
    $this->get(route('staff.pos-cards.print', $this->minor))->assertForbidden();
});

test('cashier and barista use separate login sessions and role permissions', function (): void {
    $barista = User::factory()->create();
    $baristaPermission = Permission::findOrCreate('View:BaristaBoard', 'web');
    $baristaRole = Role::findOrCreate('pos_barista', 'web');
    $baristaRole->givePermissionTo($baristaPermission);
    $barista->syncRoles($baristaRole);

    $this->post(route('cashier.login.store'), ['email' => $barista->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest('cashier');

    $this->post(route('cashier.login.store'), ['email' => $this->cashier->email, 'password' => 'password'])
        ->assertRedirect(route('cashier.terminal'));
    $this->assertAuthenticatedAs($this->cashier, 'cashier');
    $this->assertGuest('barista');
    $this->get(route('barista.orders'))->assertRedirect(route('barista.login'));

    $this->post(route('barista.login.store'), ['email' => $barista->email, 'password' => 'password'])
        ->assertRedirect(route('barista.orders'));
    $this->assertAuthenticatedAs($barista, 'barista');
    $this->post(route('cashier.logout'))->assertRedirect(route('cashier.login'));
    $this->assertGuest('cashier');
    $this->assertAuthenticatedAs($barista, 'barista');
});

test('barista board shows active orders without granting access to cashier or admin', function (): void {
    $barista = User::factory()->create();
    $baristaPermission = Permission::findOrCreate('View:BaristaBoard', 'web');
    $baristaRole = Role::findOrCreate('pos_barista', 'web');
    $baristaRole->givePermissionTo($baristaPermission);
    $barista->syncRoles($baristaRole);

    $order = app(CreatePosOrder::class)->execute($this->cashier, $this->token, posItems($this->option->id), 1500, 'barista-order', $this->minor->id);

    $this->get(route('barista.orders'))->assertRedirect(route('barista.login'));
    $this->actingAs($this->cashier, 'barista')->get(route('barista.orders'))->assertForbidden();
    $this->actingAs($barista, 'barista')->get(route('barista.orders'))->assertSuccessful()->assertSee($order->reference);
    $this->get(route('cashier.terminal'))->assertRedirect(route('cashier.login'));
    $this->actingAs($barista, 'web')->get('/admin')->assertForbidden();

    Livewire::test(BaristaBoard::class)->assertSee($order->reference)->assertSee($this->option->product->name);
    Livewire::test('store.barista-board')->assertSee($order->reference);
    Livewire::test(BaristaBoard::class)->call('startPreparing', $order->id)->assertForbidden();

    app(ChangeOrderState::class)->execute($order, Cancelled::class);
    Livewire::test(BaristaBoard::class)->assertDontSee($order->reference);
});

test('barista can progress immediate and scheduled orders through preparation', function (): void {
    $barista = User::factory()->create();
    $baristaRole = Role::findOrCreate('pos_barista', 'web');
    $baristaRole->givePermissionTo(
        Permission::findOrCreate('View:BaristaBoard', 'web'),
        Permission::findOrCreate('Prepare:BaristaOrders', 'web'),
    );
    $barista->syncRoles($baristaRole);

    $immediate = app(CreatePosOrder::class)->execute($this->cashier, $this->token, posItems($this->option->id), 1500, 'barista-preparation', $this->minor->id);
    $this->actingAs($barista, 'barista');

    Livewire::test(BaristaBoard::class)->assertSee('wire:click="startPreparing('.$immediate->id.')"', false);
    Livewire::test(BaristaBoard::class)->call('startPreparing', $immediate->id)->assertHasNoErrors();
    expect($immediate->fresh()->status->getValue())->toBe('preparing');

    Livewire::test(BaristaBoard::class)->assertSee('wire:click="markReady('.$immediate->id.')"', false);
    Livewire::test(BaristaBoard::class)->call('markReady', $immediate->id)->assertHasNoErrors();
    expect($immediate->fresh()->status->getValue())->toBe('ready_for_pickup')
        ->and($immediate->statusHistory()->where('to_status', 'ready_for_pickup')->sole()->actor_user_id)->toBe($barista->id);

    $scheduled = Order::factory()->create([
        'status' => 'confirmed',
        'pickup_type' => 'scheduled',
        'pickup_at' => now()->addMinutes(30),
    ]);

    Livewire::test(BaristaBoard::class)->assertSee('wire:click="acceptScheduled('.$scheduled->id.')"', false);
    Livewire::test(BaristaBoard::class)->call('startPreparing', $scheduled->id)->assertHasErrors('status');
    Livewire::test(BaristaBoard::class)->call('acceptScheduled', $scheduled->id)->assertHasNoErrors();
    Livewire::test(BaristaBoard::class)->call('startPreparing', $scheduled->id)->assertHasNoErrors();
    expect($scheduled->fresh()->status->getValue())->toBe('preparing');
});

test('scheduled orders stay upcoming and cannot enter preparation until the 45 minute window', function (): void {
    $barista = User::factory()->create();
    $barista->givePermissionTo(
        Permission::findOrCreate('View:BaristaBoard', 'web'),
        Permission::findOrCreate('Prepare:BaristaOrders', 'web'),
    );
    $pickupAt = now()->addDay()->setTime(12, 0);
    $scheduled = Order::factory()->create([
        'status' => 'confirmed',
        'pickup_type' => 'scheduled',
        'pickup_at' => $pickupAt,
    ]);

    $this->actingAs($barista, 'barista');
    Livewire::test(BaristaBoard::class)
        ->assertSee('طلبات قادمة')
        ->assertSee($pickupAt->format('Y-m-d H:i'))
        ->assertSee('wire:click="acceptScheduled('.$scheduled->id.')"', false)
        ->assertDontSee('wire:click="startPreparing('.$scheduled->id.')"', false)
        ->call('acceptScheduled', $scheduled->id)
        ->assertHasNoErrors();

    Livewire::test(BaristaBoard::class)->call('startPreparing', $scheduled->id)->assertHasErrors('status');
    expect(fn () => app(ChangeOrderState::class)->execute($scheduled, Preparing::class, $barista->id))
        ->toThrow(ValidationException::class);
    expect($scheduled->fresh()->status->getValue())->toBe('accepted');

    $this->travelTo($pickupAt->copy()->subMinutes(46));
    Livewire::test(BaristaBoard::class)->call('startPreparing', $scheduled->id)->assertHasErrors('status');

    $this->travelTo($pickupAt->copy()->subMinutes(45));
    Livewire::test(BaristaBoard::class)
        ->assertSee('wire:click="startPreparing('.$scheduled->id.')"', false)
        ->call('startPreparing', $scheduled->id)
        ->assertHasNoErrors();
    expect($scheduled->fresh()->status->getValue())->toBe('preparing');
    $this->travelBack();
});

test('pickup attendant alone confirms handoff and records the actor and time', function (): void {
    $this->seed(StaffWorkspaceRolesSeeder::class);
    $attendant = User::factory()->create();
    $attendant->syncRoles(Role::findByName('pos_pickup_attendant', 'web'));
    $order = app(CreatePosOrder::class)->execute($this->cashier, $this->token, posItems($this->option->id), 1500, 'pickup-handoff', $this->minor->id);
    app(ChangeOrderState::class)->execute($order, Preparing::class);
    app(ChangeOrderState::class)->execute($order, ReadyForPickup::class);

    $this->get(route('pickup.orders'))->assertRedirect(route('pickup.login'));
    $this->post(route('pickup.login.store'), ['email' => $this->cashier->email, 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest('pickup');
    $this->post(route('pickup.login.store'), ['email' => $attendant->email, 'password' => 'password'])
        ->assertRedirect(route('pickup.orders'));
    $this->assertAuthenticatedAs($attendant, 'pickup');
    Livewire::test('store.pickup-board')->assertSee($order->reference);
    Livewire::test(PickupBoard::class)->set('search', $order->reference)->assertSee($order->reference);
    Livewire::test(PickupBoard::class)
        ->assertSee('wire:click="completeOrder('.$order->id.')"', false)
        ->call('completeOrder', $order->id)
        ->assertHasNoErrors()
        ->assertDontSee($order->reference);

    $history = $order->statusHistory()->where('to_status', 'completed')->sole();
    expect($order->fresh()->status->getValue())->toBe('completed')
        ->and($history->actor_user_id)->toBe($attendant->id)
        ->and($history->created_at)->not->toBeNull();

    Livewire::test(PickupBoard::class)->call('completeOrder', $order->id)->assertHasErrors('status');
    $this->post(route('pickup.logout'))->assertRedirect(route('pickup.login'));
    $this->assertGuest('pickup');
});

test('pickup board viewing permission does not grant completion', function (): void {
    $viewer = User::factory()->create();
    $viewer->givePermissionTo(Permission::findOrCreate('View:PickupBoard', 'web'));
    $order = Order::factory()->create(['status' => 'ready_for_pickup']);

    $this->actingAs($viewer, 'pickup')->get(route('pickup.orders'))->assertSuccessful();
    Livewire::test(PickupBoard::class)
        ->assertSee($order->reference)
        ->assertDontSee('wire:click="completeOrder('.$order->id.')"', false)
        ->call('completeOrder', $order->id)
        ->assertForbidden();
    expect($order->fresh()->status->getValue())->toBe('ready_for_pickup');
});

test('only card managers can issue and print a card from the minors resource', function (): void {
    $manager = User::factory()->create();
    $manager->givePermissionTo(Permission::findOrCreate('Manage:PosCards', 'web'));
    $this->actingAs($manager, 'web')->get(route('filament.admin.resources.minor-profiles.index'))->assertSuccessful();

    Livewire::test(ListMinorProfiles::class)
        ->assertActionVisible(TestAction::make('issuePosCard')->table($this->minor))
        ->callAction(TestAction::make('issuePosCard')->table($this->minor))
        ->assertActionVisible(TestAction::make('printPosCard')->table($this->minor));

    $this->get(route('staff.pos-cards.print', $this->minor))
        ->assertSuccessful()
        ->assertSee('<svg', false)
        ->assertSee($this->minor->member_code);

    $credential = $this->minor->posCredential->fresh();
    expect($credential->getRawOriginal('token_ciphertext'))->not->toBe($credential->token_ciphertext);

    $outsider = User::factory()->create();
    $this->actingAs($outsider, 'web')->get(route('staff.pos-cards.print', $this->minor))->assertForbidden();

    app(ManageMinorPosCredential::class)->revoke($this->minor);
    $this->actingAs($manager, 'web')->get(route('staff.pos-cards.print', $this->minor))->assertNotFound();
});

test('guardian can immediately revoke only their own leader card', function (): void {
    $otherGuardian = Customer::factory()->create();
    $route = route('customer.minor-profiles.pos-card.revoke', $this->minor);

    $this->actingAs($otherGuardian, 'customer')->post($route)->assertNotFound();
    expect($this->minor->posCredential->fresh()->token_hash)->not->toBeNull();

    $this->actingAs($this->guardian, 'customer')->post($route)->assertRedirect();
    expect($this->minor->posCredential->fresh()->token_hash)->toBeNull();
    expect(fn () => app(ManageMinorPosCredential::class)->resolve($this->token))->toThrow(ValidationException::class);
});

test('guardian can revoke a QR card when wallet payments are disabled', function (): void {
    config(['byruhaa.wallets.enabled' => false]);

    $this->actingAs($this->guardian, 'customer')
        ->get(route('customer.minor-profiles.index'))
        ->assertSuccessful()
        ->assertSee('إبطال البطاقة المفقودة')
        ->assertDontSee('رمز الشراء (٦ أرقام)');
});

test('cashier terminal requires a visible total review before QR wallet payment', function (): void {
    $this->actingAs($this->cashier, 'cashier');

    $terminal = Livewire::test(PosTerminal::class)
        ->call('selectBuyerType', 'minor')
        ->assertSee('فتح كاميرا الجهاز لمسح QR')
        ->call('addOption', $this->option->id)
        ->set('scanToken', $this->token)
        ->call('scan')
        ->assertSet('selectedProfileId', $this->minor->id);

    $terminal->call('pay')->assertHasErrors(['cart']);
    expect(Order::query()->count())->toBe(0);

    $terminal->call('review')->assertSet('reviewedTotalBaisa', 750)
        ->assertSet('showReviewModal', true)
        ->assertSee('مراجعة الطلب')
        ->call('pay')->assertHasNoErrors()
        ->assertSet('showReviewModal', false);

    $order = Order::query()->sole();
    expect($order->status->getValue())->toBe('confirmed')
        ->and($order->total_baisa)->toBe(750)
        ->and($order->pos_cashier_user_id)->toBe($this->cashier->id)
        ->and($order->statusHistory()->where('to_status', 'confirmed')->sole()->actor_user_id)->toBe($this->cashier->id)
        ->and($this->wallet->refresh()->balance_baisa)->toBe(4250);
});

test('cashier can confirm a guest cash order without an account or phone', function (): void {
    $this->actingAs($this->cashier, 'cashier');

    Livewire::test(PosTerminal::class)
        ->call('addOption', $this->option->id)
        ->call('review')
        ->assertSet('reviewedTotalBaisa', 1000)
        ->set('cashReceived', '٢٫٠٠٠')
        ->call('pay')
        ->assertHasNoErrors()
        ->assertSee('الباقي: 1.000 ر.ع')
        ->assertSee('طباعة الإيصال');

    $order = Order::query()->sole();
    expect($order->payment_method)->toBe('cash')
        ->and($order->status->getValue())->toBe('confirmed')
        ->and($order->customer_id)->toBeNull()
        ->and($order->customer_phone)->toBeNull()
        ->and($order->customer_name)->toBe('ضيف نقطة البيع')
        ->and($order->cash_received_baisa)->toBe(2000)
        ->and($order->cash_change_baisa)->toBe(1000)
        ->and($order->paid_at)->not->toBeNull()
        ->and($order->payment_reference)->toBe('CASH-'.$order->reference)
        ->and($order->pos_cashier_user_id)->toBe($this->cashier->id)
        ->and($this->option->refresh()->stock_on_hand)->toBe(4)
        ->and($this->wallet->refresh()->balance_baisa)->toBe(5000);

    $barista = User::factory()->create();
    $baristaRole = Role::findOrCreate('pos_barista', 'web');
    $baristaRole->givePermissionTo(Permission::findOrCreate('View:BaristaBoard', 'web'));
    $barista->syncRoles($baristaRole);
    $this->actingAs($barista, 'barista');
    Livewire::test(BaristaBoard::class)->assertSee($order->reference);
});

test('cashier can adjust quantities on a tablet and receive exact cash from the review modal', function (): void {
    $this->actingAs($this->cashier, 'cashier');

    $terminal = Livewire::test(PosTerminal::class)
        ->call('addOption', $this->option->id)
        ->call('addOption', $this->option->id)
        ->assertSet('cart', [$this->option->id => 2])
        ->assertSee('زيادة كمية')
        ->call('decrementOption', $this->option->id)
        ->assertSet('cart', [$this->option->id => 1])
        ->call('review')
        ->assertSet('showReviewModal', true)
        ->assertSee('استلام المبلغ كاملًا')
        ->call('useExactCash')
        ->assertSet('cashReceived', '1.000')
        ->call('pay')
        ->assertHasNoErrors()
        ->assertDispatched('pos-order-completed')
        ->assertSee('طباعة الإيصال');

    $order = Order::query()->sole();

    expect($order->cash_received_baisa)->toBe(1000)
        ->and($order->cash_change_baisa)->toBe(0);

    $terminal->assertSee(route('cashier.orders.receipt', $order), false);
});

test('cashier can print only their own paid POS receipts', function (): void {
    $order = app(CreateCashPosOrder::class)->execute($this->cashier, [
        'buyer_type' => 'guest',
        'items' => posItems($this->option->id, 1),
        'reviewed_total_baisa' => 1000,
        'cash_received_baisa' => 2000,
        'idempotency_key' => 'print-sale',
    ]);
    $receiptUrl = route('cashier.orders.receipt', $order);

    $this->get($receiptUrl)->assertRedirect(route('cashier.login'));

    $otherCashier = User::factory()->create();
    $otherCashier->syncRoles(Role::findByName('pos_cashier', 'web'));
    $this->actingAs($otherCashier, 'cashier')->get($receiptUrl)->assertNotFound();

    $this->actingAs($this->cashier, 'cashier')
        ->get($receiptUrl)
        ->assertSuccessful()
        ->assertSee($order->reference)
        ->assertSee('النقد المستلم')
        ->assertSee('طباعة')
        ->assertSee('window.print()', false)
        ->assertDontSee('تحميل PDF');

    $unpaid = Order::factory()->create([
        'payment_method' => 'cash',
        'pos_cashier_user_id' => $this->cashier->id,
        'status' => 'pending_payment',
        'paid_at' => null,
    ]);
    $this->get(route('cashier.orders.receipt', $unpaid))->assertNotFound();
});

test('cashier can find todays orders and reprint a colleagues receipt only by exact reference', function (): void {
    $this->seed(StaffWorkspaceRolesSeeder::class);
    $colleague = User::factory()->create();
    $colleague->syncRoles(Role::findByName('pos_cashier', 'web'));
    $ownOrder = app(CreateCashPosOrder::class)->execute($this->cashier, [
        'buyer_type' => 'guest',
        'items' => posItems($this->option->id, 1),
        'reviewed_total_baisa' => 1000,
        'cash_received_baisa' => 1000,
        'idempotency_key' => 'cashier-own-shift',
    ]);
    $colleagueOrder = app(CreateCashPosOrder::class)->execute($colleague, [
        'buyer_type' => 'guest',
        'items' => posItems($this->option->id, 1),
        'reviewed_total_baisa' => 1000,
        'cash_received_baisa' => 1000,
        'idempotency_key' => 'cashier-colleague-shift',
    ]);
    $olderOrder = Order::factory()->create([
        'status' => 'confirmed',
        'payment_method' => 'cash',
        'paid_at' => now()->subDay(),
        'created_at' => now()->subDay(),
        'pos_cashier_user_id' => $colleague->id,
    ]);
    $nonPosOrder = Order::factory()->create([
        'status' => 'confirmed',
        'paid_at' => now(),
        'pos_cashier_user_id' => null,
    ]);
    $unpaidColleagueOrder = Order::factory()->create([
        'status' => 'pending_payment',
        'paid_at' => null,
        'pos_cashier_user_id' => $colleague->id,
    ]);

    $this->actingAs($this->cashier, 'cashier');
    $this->get(route('cashier.orders'))->assertSuccessful()->assertSee($ownOrder->reference)->assertDontSee($colleagueOrder->reference);
    Livewire::test('store.cashier-orders')->assertSee($ownOrder->reference);
    Livewire::test(CashierOrders::class)
        ->set('search', $colleagueOrder->reference)
        ->assertSee($colleagueOrder->reference)
        ->assertDontSee($ownOrder->reference)
        ->assertSee(route('cashier.orders.receipt', $colleagueOrder), false);

    $this->get(route('cashier.orders.receipt', $colleagueOrder))->assertSuccessful();
    $this->get(route('cashier.orders.receipt', $olderOrder))->assertNotFound();
    $this->get(route('cashier.orders.receipt', $nonPosOrder))->assertNotFound();
    Livewire::test(CashierOrders::class)->set('search', $unpaidColleagueOrder->reference)->assertDontSee($unpaidColleagueOrder->reference);

    $this->actingAs($colleague, 'cashier')->get(route('cashier.orders.receipt', $ownOrder))->assertSuccessful();
});

test('a cashier without reprint permission cannot inspect a colleagues order', function (): void {
    $colleague = User::factory()->create();
    $colleague->givePermissionTo(Permission::findOrCreate('Sell:Pos', 'web'));
    $order = Order::factory()->create([
        'status' => 'confirmed',
        'payment_method' => 'cash',
        'paid_at' => now(),
        'pos_cashier_user_id' => $colleague->id,
    ]);

    $this->actingAs($this->cashier, 'cashier');
    Livewire::test(CashierOrders::class)->set('search', $order->reference)->assertDontSee($order->reference);
    $this->get(route('cashier.orders.receipt', $order))->assertNotFound();
});

test('minor can pay cash with QR even when wallet spending is disabled', function (): void {
    $this->minor->update(['wallet_spending_enabled' => false]);
    $this->actingAs($this->cashier, 'cashier');

    $terminal = Livewire::test(PosTerminal::class)
        ->call('selectBuyerType', 'minor')
        ->call('selectPaymentMethod', 'cash')
        ->call('addOption', $this->option->id)
        ->set('scanToken', $this->token)
        ->call('scan')
        ->call('review')
        ->assertSet('reviewedTotalBaisa', 750)
        ->set('cashReceived', '1.000');

    $terminal->call('pay')->assertHasNoErrors();

    $order = Order::query()->sole();
    expect($order->minor_profile_id)->toBe($this->minor->id)
        ->and($order->customer_id)->toBe($this->guardian->id)
        ->and($order->payment_method)->toBe('cash')
        ->and($order->total_baisa)->toBe(750)
        ->and($order->cash_change_baisa)->toBe(250)
        ->and($order->status->getValue())->toBe('confirmed')
        ->and($this->wallet->refresh()->balance_baisa)->toBe(5000);

    $this->actingAs($this->guardian, 'customer')
        ->get(route('customer.store.orders.receipt', $order->payment_token))
        ->assertSuccessful()
        ->assertSee('النقد المستلم')
        ->assertSee('الباقي');
});

test('cash sale requires enough money and keeps an idempotency key tied to its cart', function (): void {
    $sale = [
        'buyer_type' => 'guest',
        'items' => posItems($this->option->id, 1),
        'reviewed_total_baisa' => 1000,
        'cash_received_baisa' => 500,
        'idempotency_key' => 'cash-sale',
    ];

    expect(fn () => app(CreateCashPosOrder::class)->execute($this->cashier, $sale))
        ->toThrow(ValidationException::class);
    expect(Order::query()->count())->toBe(0);

    $sale['cash_received_baisa'] = 1000;
    $order = app(CreateCashPosOrder::class)->execute($this->cashier, $sale);
    $retry = app(CreateCashPosOrder::class)->execute($this->cashier, $sale);

    expect($retry->id)->toBe($order->id)
        ->and(Order::query()->count())->toBe(1)
        ->and($this->option->refresh()->stock_on_hand)->toBe(4);

    $sale['items'] = posItems($this->option->id, 2);
    $sale['reviewed_total_baisa'] = 2000;
    $sale['cash_received_baisa'] = 2000;

    expect(fn () => app(CreateCashPosOrder::class)->execute($this->cashier, $sale))
        ->toThrow(ValidationException::class);
});

test('cash checkout rejects unauthorized cashiers and changed prices without saving an order', function (): void {
    $sale = [
        'buyer_type' => 'guest',
        'items' => posItems($this->option->id, 1),
        'reviewed_total_baisa' => 1000,
        'cash_received_baisa' => 2000,
        'idempotency_key' => 'stale-cash-sale',
    ];
    $outsider = User::factory()->create();
    $outsider->syncRoles(Role::findOrCreate('unrelated_staff', 'web'));

    expect(fn () => app(CreateCashPosOrder::class)->execute($outsider, $sale))
        ->toThrow(HttpException::class);

    $this->option->update(['price_baisa' => 1200]);
    expect(fn () => app(CreateCashPosOrder::class)->execute($this->cashier, $sale))
        ->toThrow(ValidationException::class);
    expect(Order::query()->count())->toBe(0)
        ->and($this->option->refresh()->stock_on_hand)->toBe(5);
});

test('duplicate POS submission charges and consumes stock once and rejects a changed cart', function (): void {
    $action = app(CreatePosOrder::class);
    $order = $action->execute($this->cashier, $this->token, posItems($this->option->id), 1500, 'same-sale', $this->minor->id);
    $retry = $action->execute($this->cashier, $this->token, posItems($this->option->id), 1500, 'same-sale', $this->minor->id);

    expect($retry->id)->toBe($order->id)
        ->and(Order::query()->count())->toBe(1)
        ->and($this->wallet->refresh()->balance_baisa)->toBe(3500)
        ->and($this->wallet->movements()->where('type', 'purchase')->count())->toBe(1)
        ->and($this->option->refresh()->stock_on_hand)->toBe(3);

    expect(fn () => $action->execute($this->cashier, $this->token, posItems($this->option->id, 1), 750, 'same-sale', $this->minor->id))
        ->toThrow(ValidationException::class);
});

test('insufficient or suspended wallets roll back POS orders and inventory', function (string $restriction): void {
    if ($restriction === 'insufficient') {
        $this->wallet->update(['balance_baisa' => 0]);
    } else {
        $this->wallet->update(['status' => 'suspended']);
    }

    expect(fn () => app(CreatePosOrder::class)->execute($this->cashier, $this->token, posItems($this->option->id), 1500, 'blocked-sale', $this->minor->id))
        ->toThrow(ValidationException::class);

    expect(Order::query()->count())->toBe(0)
        ->and($this->wallet->movements()->where('type', 'purchase')->count())->toBe(0)
        ->and($this->option->refresh()->stock_on_hand)->toBe(5);
})->with(['insufficient', 'suspended']);

test('suspended leader and disabled wallet spending block payment', function (string $restriction): void {
    if ($restriction === 'leader') {
        $this->minor->update(['status' => 'suspended']);
    } else {
        $this->minor->update(['wallet_spending_enabled' => false]);
    }

    expect(fn () => app(CreatePosOrder::class)->execute($this->cashier, $this->token, posItems($this->option->id), 1500, 'not-allowed', $this->minor->id))
        ->toThrow(ValidationException::class)
        ->and(Order::query()->count())->toBe(0);
})->with(['leader', 'spending']);

test('price changes after review require a new total confirmation', function (): void {
    $this->option->update(['member_price_baisa' => 700]);

    expect(fn () => app(CreatePosOrder::class)->execute($this->cashier, $this->token, posItems($this->option->id), 1500, 'stale-total', $this->minor->id))
        ->toThrow(ValidationException::class)
        ->and(Order::query()->count())->toBe(0)
        ->and($this->wallet->refresh()->balance_baisa)->toBe(5000)
        ->and($this->option->refresh()->stock_on_hand)->toBe(5);
});

test('changing the scanned leader after review cannot redirect a charge', function (): void {
    expect(fn () => app(CreatePosOrder::class)->execute(
        $this->cashier,
        $this->token,
        posItems($this->option->id),
        1500,
        'swapped-card',
        $this->minor->id + 1,
    ))->toThrow(ValidationException::class);

    expect(Order::query()->count())->toBe(0)
        ->and($this->wallet->refresh()->balance_baisa)->toBe(5000);
});

test('invalid QR cards cannot create wallet orders', function (): void {
    expect(fn () => app(CreatePosOrder::class)->execute($this->cashier, 'invalid', posItems($this->option->id), 1500, 'invalid-card', $this->minor->id))
        ->toThrow(ValidationException::class)
        ->and(Order::query()->count())->toBe(0);
});

test('replaced and revoked cards stop working without revealing the token in storage', function (): void {
    $credentials = app(ManageMinorPosCredential::class);
    $firstHash = $this->minor->posCredential->token_hash;
    expect($firstHash)->not->toBe($this->token);

    $replacement = $credentials->issue($this->minor);
    expect(fn () => $credentials->resolve($this->token))->toThrow(ValidationException::class);
    expect($credentials->resolve($replacement)->profileId)->toBe($this->minor->id);

    $credentials->revoke($this->minor);
    expect(fn () => $credentials->resolve($replacement))->toThrow(ValidationException::class);
});

test('cancelling a paid POS order reverses the wallet purchase once', function (): void {
    $order = app(CreatePosOrder::class)->execute($this->cashier, $this->token, posItems($this->option->id), 1500, 'refund-sale', $this->minor->id);
    $cancelled = app(ChangeOrderState::class)->execute($order, Cancelled::class, $this->cashier->id, 'Customer cancelled');
    app(WalletService::class)->reversePurchase($order->reference);

    expect($cancelled->status->getValue())->toBe('cancelled')
        ->and($this->wallet->refresh()->balance_baisa)->toBe(5000)
        ->and($this->wallet->movements()->where('type', 'purchase_reversal')->count())->toBe(1)
        ->and($this->option->refresh()->stock_on_hand)->toBe(3);
});

test('order viewers cannot initiate POS cancellation or refund', function (): void {
    $order = app(CreatePosOrder::class)->execute($this->cashier, $this->token, posItems($this->option->id), 1500, 'viewer-sale', $this->minor->id);
    $viewer = User::factory()->create();
    $role = Role::findOrCreate('pos_order_viewer', 'web');
    $role->givePermissionTo(Permission::findOrCreate('View:Order', 'web'));
    $role->givePermissionTo(Permission::findOrCreate('ViewAny:Order', 'web'));
    $viewer->syncRoles($role);

    $this->actingAs($viewer, 'web');
    $this->get(OrderResource::getUrl('view', ['record' => $order]))
        ->assertSuccessful()
        ->assertDontSee(__('admin.store.actions.change_status'));
});

test('POS purchase emits one parent webhook path through the wallet movement', function (): void {
    $order = app(CreatePosOrder::class)->execute($this->cashier, $this->token, posItems($this->option->id), 1500, 'notify-sale', $this->minor->id);
    $sender = $this->mock(ByruhaaWebhookSender::class);
    $sender->shouldReceive('sendUchatOrderState')->never();
    $sender->shouldReceive('sendUchatWalletMovement')->once();

    app(SendUchatOrderStateWebhook::class)->handle(new OrderStateChanged($order, 'pending_payment'));
    app(SendWalletMovementNotification::class)->handle(new WalletMovementPosted($this->wallet->movements()->where('type', 'purchase')->sole()));
});
