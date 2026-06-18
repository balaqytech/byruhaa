<?php

use App\Models\Booking;
use App\Models\BookingFamilyMember;
use App\Models\Customer;
use App\Models\Event;
use App\Models\FamilyMember;
use App\Models\User;
use App\Services\BookingApprovalService;
use Livewire\Livewire;

test('family members page renders existing family members in a table', function () {
    $customer = Customer::factory()->create();
    $familyMember = FamilyMember::factory()
        ->for($customer)
        ->create([
            'name' => 'Salim Al Balushi',
            'school_name' => 'Horizon School',
            'grade' => '6',
        ]);

    $this->actingAs($customer, 'customer')
        ->get(route('family-members.index'))
        ->assertOk()
        ->assertSee('Salim Al Balushi')
        ->assertSee('Horizon School')
        ->assertSee('6')
        ->assertSee((string) $familyMember->birth_date->age);
});

test('customer can create a family member from the panel modal action', function () {
    $customer = Customer::factory()->create();
    $this->actingAs($customer, 'customer');

    Livewire::test('pages::family-members.index')
        ->set('name', 'Maha Al Harthy')
        ->set('birth_date', now()->subYears(12)->format('Y-m-d'))
        ->set('school_name', 'Future School')
        ->set('grade', '7')
        ->call('saveFamilyMember')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('family_members', [
        'customer_id' => $customer->id,
        'name' => 'Maha Al Harthy',
        'school_name' => 'Future School',
        'grade' => '7',
    ]);
});

test('customer can edit their family member from the panel modal action', function () {
    $customer = Customer::factory()->create();
    $familyMember = FamilyMember::factory()
        ->for($customer)
        ->create([
            'name' => 'Old Name',
            'school_name' => 'Old School',
        ]);

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::family-members.index')
        ->call('editFamilyMember', $familyMember->id)
        ->assertSet('editingFamilyMemberId', $familyMember->id)
        ->assertSet('name', 'Old Name')
        ->set('name', 'Updated Name')
        ->set('school_name', 'Updated School')
        ->call('saveFamilyMember')
        ->assertHasNoErrors();

    expect($familyMember->refresh())
        ->name->toBe('Updated Name')
        ->school_name->toBe('Updated School');
});

test('bookings page renders existing bookings in a table', function () {
    $customer = Customer::factory()->create();
    $event = Event::factory()->create(['name' => 'Mountain Trip']);
    $booking = Booking::factory()
        ->for($customer)
        ->for($event)
        ->create(['reference' => 'BRH-10001']);

    BookingFamilyMember::factory()
        ->for($booking)
        ->for(FamilyMember::factory()->for($customer))
        ->create();

    $this->actingAs($customer, 'customer')
        ->get(route('bookings.index'))
        ->assertOk()
        ->assertSee('BRH-10001')
        ->assertSee('Mountain Trip')
        ->assertSee(__('ui.actions.open'));
});

test('booking details page renders the improved hugeicons contract layout', function () {
    $staff = User::factory()->create();
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'name' => 'Desert Camp',
        'contract_terms_html' => '<p>Safety terms must be reviewed before signature.</p>',
    ]);
    $familyMember = FamilyMember::factory()->for($customer)->create(['name' => 'Maha Al Harthy']);
    $booking = Booking::factory()
        ->for($customer)
        ->for($event)
        ->create(['reference' => 'BRH-20002']);

    BookingFamilyMember::factory()
        ->for($booking)
        ->for($familyMember)
        ->create();

    app(BookingApprovalService::class)->approve($booking, $staff);

    $this->actingAs($customer, 'customer')
        ->get(route('bookings.show', $booking))
        ->assertOk()
        ->assertSee('BRH-20002')
        ->assertSee('Desert Camp')
        ->assertSee('Maha Al Harthy')
        ->assertSee(__('ui.bookings.contract_terms'))
        ->assertSee('Safety terms must be reviewed before signature.')
        ->assertSee('hgi-stroke', false)
        ->assertSee('hgi-contracts', false)
        ->assertSee(__('ui.actions.sign_contract'));
});
