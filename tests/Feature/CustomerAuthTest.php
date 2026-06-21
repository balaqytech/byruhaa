<?php

use App\Models\Customer;
use App\Models\User;

test('customers can register with email', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Mona Said',
        'email' => 'mona@example.com',
        'phone_number' => '91234567',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('customer.dashboard', absolute: false));

    $this->assertAuthenticated('customer');
    $customer = Customer::where('email', 'mona@example.com')->firstOrFail();

    expect($customer->phone_number)->toBe('+96891234567')
        ->and($customer->hasCompleteProfile())->toBeFalse()
        ->and($customer->missingRequiredProfileFields())->toBe(['civil_id', 'address', 'wilaya', 'area']);
});

test('customers cannot register without a phone number', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Mona Said',
        'email' => 'mona@example.com',
        'phone_number' => null,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('phone_number');

    $this->assertGuest('customer');
    expect(Customer::query()->count())->toBe(0);
});

test('customers can register and login with an omani phone number', function () {
    $this->post(route('register.store'), [
        'name' => 'Ali Said',
        'email' => null,
        'phone_number' => '91234567',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasNoErrors();

    auth('customer')->logout();

    $customer = Customer::firstOrFail();

    expect($customer->phone_number)->toBe('+96891234567');

    $this->post(route('login.store'), [
        'login' => '91234567',
        'password' => 'password',
    ])->assertSessionHasNoErrors()
        ->assertRedirect(route('customer.dashboard', absolute: false));

    $this->assertAuthenticatedAs($customer, 'customer');
});

test('customers cannot access filament while staff users can', function () {
    $customer = Customer::factory()->create();
    $staff = User::factory()->create();

    $this->actingAs($customer, 'customer')
        ->get('/admin')
        ->assertRedirect('/admin/login');

    $this->actingAs($staff, 'web')
        ->get('/admin')
        ->assertOk();
});
