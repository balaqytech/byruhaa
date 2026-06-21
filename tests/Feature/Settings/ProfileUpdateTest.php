<?php

use App\Models\Customer;
use Livewire\Livewire;

test('profile page is displayed', function () {
    $customer = Customer::factory()->create();

    $this->actingAs($customer, 'customer')
        ->get(route('customer.profile.edit'))
        ->assertOk();
});

test('profile information can be updated', function () {
    $customer = Customer::factory()->create();

    $this->actingAs($customer, 'customer');

    $response = Livewire::test('pages::customer.settings.profile')
        ->set('name', 'Test Customer')
        ->set('email', 'test@example.com')
        ->set('phone_number', null)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $customer->refresh();

    expect($customer->name)->toEqual('Test Customer');
    expect($customer->email)->toEqual('test@example.com');
});

test('profile phone number is normalized', function () {
    $customer = Customer::factory()->create(['phone_number' => null]);

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::customer.settings.profile')
        ->set('name', $customer->name)
        ->set('email', null)
        ->set('phone_number', '91234567')
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    expect($customer->refresh()->phone_number)->toBe('+96891234567');
});
