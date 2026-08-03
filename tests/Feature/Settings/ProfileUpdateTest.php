<?php

use App\Modules\Identity\Models\Customer;
use Livewire\Livewire;

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
