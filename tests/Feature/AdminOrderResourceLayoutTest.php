<?php

use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Store\Filament\Resources\Orders\Pages\CreateOrder;
use App\Modules\Store\Filament\Resources\Orders\Pages\ListOrders;
use App\Modules\Store\Filament\Resources\Orders\Pages\ViewOrder;
use App\Modules\Store\Models\Order;
use App\Modules\Store\Models\OrderItem;
use Filament\Schemas\Components\Wizard;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

test('order list tabs filter each operational status', function (): void {
    $this->actingAs(User::factory()->create(), 'web');

    $pending = Order::factory()->create(['status' => 'pending_payment']);
    $confirmed = Order::factory()->create(['status' => 'confirmed']);
    $completed = Order::factory()->create(['status' => 'completed']);
    $refunded = Order::factory()->create(['status' => 'refunded']);

    Livewire::test(ListOrders::class)
        ->assertActionExists('create')
        ->assertActionExists('create_guest')
        ->set('activeTab', 'pending_payment')
        ->assertCanSeeTableRecords([$pending])
        ->assertCanNotSeeTableRecords([$confirmed, $completed, $refunded]);

    Livewire::test(ListOrders::class)
        ->set('activeTab', 'confirmed')
        ->assertCanSeeTableRecords([$confirmed])
        ->assertCanNotSeeTableRecords([$pending, $completed, $refunded]);

    Livewire::test(ListOrders::class)
        ->set('activeTab', 'refunded')
        ->assertCanSeeTableRecords([$refunded])
        ->assertCanNotSeeTableRecords([$pending, $confirmed, $completed]);
});

test('order list can filter by payment and pickup method', function (): void {
    $this->actingAs(User::factory()->create(), 'web');

    $walletPickup = Order::factory()->create(['payment_method' => 'wallet', 'pickup_type' => 'scheduled']);
    $directPickup = Order::factory()->create(['payment_method' => 'thawani', 'pickup_type' => 'immediate']);

    Livewire::test(ListOrders::class)
        ->assertTableColumnExists('payment_method')
        ->filterTable('payment_method', 'wallet')
        ->assertCanSeeTableRecords([$walletPickup])
        ->assertCanNotSeeTableRecords([$directPickup]);

    Livewire::test(ListOrders::class)
        ->filterTable('pickup_type', 'scheduled')
        ->assertCanSeeTableRecords([$walletPickup])
        ->assertCanNotSeeTableRecords([$directPickup]);
});

test('admin order creation uses a wizard while order details remain grouped', function (): void {
    $this->actingAs(User::factory()->create(), 'web');
    $order = Order::factory()->create();
    OrderItem::factory()->for($order)->create(['note' => 'No sugar']);

    $createPage = Livewire::test(CreateOrder::class)
        ->assertSuccessful()
        ->assertSee(__('admin.store.order_tabs.customer_payment'))
        ->assertSee(__('admin.store.sections.items'))
        ->assertSee(__('admin.store.order_tabs.pickup_notes'))
        ->assertFormFieldExists('customer_id')
        ->assertFormFieldExists('items');

    expect($createPage->instance()->getWizardComponent())->toBeInstanceOf(Wizard::class)
        ->and($createPage->instance()->getSteps())->toHaveCount(3);

    Livewire::test(ViewOrder::class, ['record' => $order->getRouteKey()])
        ->assertSuccessful()
        ->assertSee(__('admin.store.order_tabs.customer_payment'))
        ->assertSee(__('admin.store.sections.status_history'))
        ->assertSee($order->reference)
        ->assertSee('TEST-SKU')
        ->assertSee('No sugar')
        ->assertSee(__('admin.fields.total_including_vat'));
});

test('admin can create and select a customer without leaving the order wizard', function (): void {
    $this->actingAs(User::factory()->admin()->create(), 'web');

    $createPage = Livewire::test(CreateOrder::class)
        ->assertFormComponentActionVisible('customer_id', 'createOption')
        ->callFormComponentAction('customer_id', 'createOption', [
            'name' => 'New Coffee Customer',
            'phone_number' => '91234567',
            'email' => 'coffee-customer@example.com',
            'password' => 'SecurePassword123!',
        ])
        ->assertHasNoFormErrors();

    $customer = Customer::query()->where('email', 'coffee-customer@example.com')->sole();

    $createPage->assertFormSet(['customer_id' => $customer->id]);

    expect($customer->phone_number)->toBe('+96891234567')
        ->and(Hash::check('SecurePassword123!', $customer->password))->toBeTrue();
});

test('quick customer creation rejects a phone already registered in another format', function (): void {
    $this->actingAs(User::factory()->admin()->create(), 'web');
    Customer::factory()->create(['phone_number' => '+96891234567']);

    Livewire::test(CreateOrder::class)
        ->callFormComponentAction('customer_id', 'createOption', [
            'name' => 'Duplicate Customer',
            'phone_number' => '91234567',
            'password' => 'SecurePassword123!',
        ])
        ->assertHasFormComponentActionErrors(['phone_number']);

    expect(Customer::query()->where('name', 'Duplicate Customer')->exists())->toBeFalse();
});

test('order staff without customer creation permission cannot quick create customers', function (): void {
    $staff = User::factory()->create();
    $role = Role::findOrCreate('order_only', 'web');
    $role->givePermissionTo([
        Permission::findOrCreate('ViewAny:Order', 'web'),
        Permission::findOrCreate('Create:Order', 'web'),
    ]);
    $staff->syncRoles($role);
    $this->actingAs($staff, 'web');

    Livewire::test(CreateOrder::class)
        ->assertFormComponentActionHidden('customer_id', 'createOption');
});

test('leader quick creation requires its own permission', function (): void {
    $staff = User::factory()->create();
    $role = Role::findOrCreate('order_creator_without_leaders', 'web');
    $role->givePermissionTo([
        Permission::findOrCreate('ViewAny:Order', 'web'),
        Permission::findOrCreate('Create:Order', 'web'),
    ]);
    $staff->syncRoles($role);
    $this->actingAs($staff, 'web');
    $guardian = Customer::factory()->create();

    Livewire::test(CreateOrder::class)
        ->fillForm(['customer_id' => $guardian->id])
        ->assertFormComponentActionDoesNotExist('minor_profile_id', 'quickCreateMinor');
});

test('admin can start leader account activation from the order wizard', function (): void {
    $this->actingAs(User::factory()->admin()->create(), 'web');
    $guardian = Customer::factory()->create();

    Livewire::test(CreateOrder::class)
        ->fillForm(['customer_id' => $guardian->id])
        ->assertFormComponentActionVisible('minor_profile_id', 'quickCreateMinor')
        ->callFormComponentAction('minor_profile_id', 'quickCreateMinor', [
            'name' => 'New Leader',
            'birth_date' => now()->subYears(12)->toDateString(),
            'guardian_consent_confirmed' => true,
        ])
        ->assertHasNoFormErrors()
        ->assertNotified(__('admin.store.admin_order.minor_created_pending'))
        ->assertFormSet(['minor_profile_id' => null]);

    $profile = MinorProfile::query()->sole();

    expect($profile->guardian()->is($guardian))->toBeTrue()
        ->and($profile->familyMember->name)->toBe('New Leader')
        ->and($profile->status->value)->toBe('pending_child_activation')
        ->and($profile->consents()->where('purpose', 'browser_notifications')->exists())->toBeFalse();
});

test('leader quick creation requires guardian approval', function (): void {
    $this->actingAs(User::factory()->admin()->create(), 'web');
    $guardian = Customer::factory()->create();

    Livewire::test(CreateOrder::class)
        ->fillForm(['customer_id' => $guardian->id])
        ->callFormComponentAction('minor_profile_id', 'quickCreateMinor', [
            'name' => 'No Consent',
            'birth_date' => now()->subYears(12)->toDateString(),
            'guardian_consent_confirmed' => false,
        ])
        ->assertHasFormComponentActionErrors(['guardian_consent_confirmed']);

    expect(MinorProfile::query()->count())->toBe(0);
});
