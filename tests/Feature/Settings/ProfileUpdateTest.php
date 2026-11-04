<?php

use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\User;
use Livewire\Livewire;

test('customer profile accepts and normalizes international phone formats', function (string $input, string $expected): void {
    $customer = Customer::factory()->create(['phone_verified_at' => now()]);
    $this->actingAs($customer, 'customer');

    Livewire::test('pages::customer.settings.profile')
        ->set('phone_number', $input)
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($customer->refresh()->phone_number)->toBe($expected)
        ->and($customer->phone_verified_at)->toBeNull();
})->with([
    'Yemen' => ['+967777833019', '+967777833019'],
    'international spaces' => ['+44 20 7946 0018', '+442079460018'],
    'Oman national' => ['91234567', '+96891234567'],
]);

test('invalid profile phones show an Arabic validation message', function (): void {
    app()->setLocale('ar');
    $customer = Customer::factory()->create();
    $this->actingAs($customer, 'customer');

    Livewire::test('pages::customer.settings.profile')
        ->set('phone_number', '123')
        ->call('updateProfileInformation')
        ->assertHasErrors(['phone_number' => 'phone'])
        ->assertSee('يجب أن يكون رقم الهاتف صالحًا.')
        ->assertDontSee('validation.phone');
});

test('profile required field errors use Arabic field names', function (): void {
    app()->setLocale('ar');
    $this->actingAs(Customer::factory()->create(), 'customer');

    Livewire::test('pages::customer.settings.profile')
        ->set('civil_id', '')
        ->set('address', '')
        ->set('wilaya', '')
        ->set('area', '')
        ->call('updateProfileInformation')
        ->assertHasErrors(['civil_id' => 'required', 'address' => 'required', 'wilaya' => 'required', 'area' => 'required'])
        ->assertSee('حقل الرقم المدني مطلوب.')
        ->assertSee('حقل العنوان مطلوب.')
        ->assertSee('حقل الولاية مطلوب.')
        ->assertSee('حقل المنطقة مطلوب.');
});

test('staff can save an international customer phone', function (): void {
    $this->actingAs(User::factory()->create(), 'web');
    $customer = Customer::factory()->create();

    Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
        ->fillForm(['phone_number' => '+967777833019'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($customer->refresh()->phone_number)->toBe('+967777833019');
});

test('profile page is displayed', function () {
    $customer = Customer::factory()->create();

    $this->actingAs($customer, 'customer')
        ->get(route('customer.profile.edit'))
        ->assertOk();
});

test('profile information can be updated', function () {
    $customer = Customer::factory()->incompleteProfile()->create();

    $this->actingAs($customer, 'customer');

    $response = Livewire::test('pages::customer.settings.profile')
        ->set('name', 'Test Customer')
        ->set('email', 'test@example.com')
        ->set('phone_number', '91234567')
        ->set('civil_id', '12345678')
        ->set('address', 'House 12')
        ->set('wilaya', 'Muscat')
        ->set('area', 'Qurum')
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $customer->refresh();

    expect($customer->name)->toEqual('Test Customer');
    expect($customer->email)->toEqual('test@example.com');
    expect($customer->phone_number)->toBe('+96891234567');
    expect($customer->civil_id)->toBe('12345678');
    expect($customer->address)->toBe('House 12');
    expect($customer->wilaya)->toBe('Muscat');
    expect($customer->area)->toBe('Qurum');
    expect($customer->hasCompleteProfile())->toBeTrue();
});

test('profile phone number is normalized', function () {
    $customer = Customer::factory()->create();

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::customer.settings.profile')
        ->set('name', $customer->name)
        ->set('email', null)
        ->set('phone_number', '91234567')
        ->set('civil_id', $customer->civil_id)
        ->set('address', $customer->address)
        ->set('wilaya', $customer->wilaya)
        ->set('area', $customer->area)
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($customer->refresh()->phone_number)->toBe('+96891234567');
});

test('incomplete customer sees profile completion alert until required fields are saved', function () {
    $customer = Customer::factory()->incompleteProfile()->create();

    $this->actingAs($customer, 'customer')
        ->get(route('customer.dashboard'))
        ->assertOk()
        ->assertSee(__('ui.messages.profile_incomplete_alert'))
        ->assertSee(route('customer.profile.edit'), false);

    $this->actingAs($customer->fresh(), 'customer');

    Livewire::test('pages::customer.settings.profile')
        ->set('name', $customer->name)
        ->set('email', $customer->email)
        ->set('phone_number', $customer->phone_number)
        ->set('civil_id', '12345678')
        ->set('address', 'House 12')
        ->set('wilaya', 'Muscat')
        ->set('area', 'Qurum')
        ->call('updateProfileInformation')
        ->assertHasNoErrors()
        ->assertDispatched('customer-profile-completed');

    $this->actingAs($customer->fresh(), 'customer')
        ->get(route('customer.dashboard'))
        ->assertOk()
        ->assertDontSee(__('ui.messages.profile_incomplete_alert'));
});
