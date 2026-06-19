<?php

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Discounts\DiscountResource;
use App\Filament\Resources\EventPaymentPlans\EventPaymentPlanResource;
use App\Filament\Resources\EventPaymentPlans\Pages\CreateEventPaymentPlan;
use App\Filament\Resources\Events\EventResource;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Event;
use App\Models\EventPaymentPlan;
use App\Models\EventPaymentPlanInstallment;
use App\Models\User;
use Livewire\Livewire;

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

test('staff can view event payment plans in filament', function () {
    $staff = User::factory()->create();
    $event = Event::factory()->create(['name' => 'Summer Camp']);
    $paymentPlan = EventPaymentPlan::factory()->for($event)->create(['name' => 'Two installments']);

    EventPaymentPlanInstallment::factory()->for($paymentPlan, 'paymentPlan')->create([
        'sequence' => 1,
        'percentage' => 50,
        'due_date' => '2026-07-01',
    ]);
    EventPaymentPlanInstallment::factory()->for($paymentPlan, 'paymentPlan')->create([
        'sequence' => 2,
        'percentage' => 50,
        'due_date' => '2026-08-01',
    ]);

    $this->actingAs($staff, 'web')
        ->get(EventPaymentPlanResource::getUrl('index'))
        ->assertOk()
        ->assertSee($paymentPlan->name)
        ->assertSee($event->name);
});

test('staff must enter payment plan installments that total one hundred percent', function () {
    $staff = User::factory()->create();
    $event = Event::factory()->create();

    $this->actingAs($staff, 'web');

    Livewire::test(CreateEventPaymentPlan::class)
        ->fillForm([
            'event_id' => $event->id,
            'name' => 'Invalid installments',
            'is_active' => true,
            'installments' => [
                [
                    'name' => 'First',
                    'sequence' => 1,
                    'percentage' => 50,
                    'due_date' => '2026-07-01',
                ],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['installments']);
});
