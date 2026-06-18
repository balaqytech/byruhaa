<?php

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use App\Models\User;

test('staff can view customers in filament', function () {
    $staff = User::factory()->create();
    $customer = Customer::factory()->create([
        'name' => 'Aisha Customer',
        'email' => 'aisha@example.com',
    ]);

    $this->actingAs($staff, 'web')
        ->get(CustomerResource::getUrl('index'))
        ->assertOk()
        ->assertSee($customer->name)
        ->assertSee($customer->email);
});
