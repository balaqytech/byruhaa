<?php

use App\Modules\Identity\Models\Customer;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('security settings page can be rendered', function () {
    $customer = Customer::factory()->create();

    $this->actingAs($customer, 'customer')
        ->get(route('customer.security.edit'))
        ->assertOk();
});

test('password can be updated', function () {
    $customer = Customer::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::customer.settings.security')
        ->set('current_password', 'password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check('new-password', $customer->refresh()->password))->toBeTrue();
});

test('correct password must be provided to update password', function () {
    $customer = Customer::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::customer.settings.security')
        ->set('current_password', 'wrong-password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasErrors(['current_password']);
});
