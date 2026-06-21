<?php

use App\Models\Customer;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('customer.dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $customer = Customer::factory()->create();
    $this->actingAs($customer, 'customer');

    $response = $this->get(route('customer.dashboard'));
    $response->assertOk();
});
