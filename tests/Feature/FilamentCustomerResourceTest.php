<?php

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Discounts\DiscountResource;
use App\Filament\Resources\Events\EventResource;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Event;
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

test('staff can view event prices in filament', function () {
    $staff = User::factory()->create();
    $event = Event::factory()->create([
        'name' => 'Mountain Trip',
        'price_baisa' => 12500,
    ]);

    $this->actingAs($staff, 'web')
        ->get(EventResource::getUrl('index'))
        ->assertOk()
        ->assertSee($event->name)
        ->assertSee(__('admin.fields.price'))
        ->assertSee('fi-ta-cell-price-baisa', false);
});

test('staff can view discounts in filament', function () {
    $staff = User::factory()->create();
    $discount = Discount::factory()->create([
        'name' => 'Family flat discount',
        'amount_baisa' => 2500,
    ]);

    $this->actingAs($staff, 'web')
        ->get(DiscountResource::getUrl('index'))
        ->assertOk()
        ->assertSee($discount->name)
        ->assertSee(__('admin.fields.discount_amount'))
        ->assertSee('fi-ta-cell-amount-baisa', false);
});
