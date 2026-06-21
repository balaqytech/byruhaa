<?php

use App\Enums\BookingInstallmentState;
use App\Enums\PaymentRefundState;
use App\Enums\PaymentState;
use App\Models\Booking;
use App\Models\BookingFamilyMember;
use App\Models\BookingInstallment;
use App\Models\BookingPaymentSchedule;
use App\Models\Customer;
use App\Models\Event;
use App\Models\FamilyMember;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\User;
use App\Services\BookingApprovalService;
use App\Support\MoneyFormatter;
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
        ->get(route('customer.family-members.index'))
        ->assertOk()
        ->assertSee('Salim Al Balushi')
        ->assertSee('Horizon School')
        ->assertSee('6')
        ->assertSee((string) $familyMember->birth_date->age);
});

test('customer can create a family member from the panel modal action', function () {
    $customer = Customer::factory()->create();
    $this->actingAs($customer, 'customer');

    Livewire::test('pages::customer.family-members.index')
        ->set('name', 'Maha Al Harthy')
        ->set('birth_date', now()->subYears(12)->format('Y-m-d'))
        ->set('school_name', 'Future School')
        ->set('grade', '7')
        ->set('relationship_to_customer', 'Daughter')
        ->call('saveFamilyMember')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('family_members', [
        'customer_id' => $customer->id,
        'name' => 'Maha Al Harthy',
        'school_name' => 'Future School',
        'grade' => '7',
        'relationship_to_customer' => 'Daughter',
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

    Livewire::test('pages::customer.family-members.index')
        ->call('editFamilyMember', $familyMember->id)
        ->assertSet('editingFamilyMemberId', $familyMember->id)
        ->assertSet('name', 'Old Name')
        ->set('name', 'Updated Name')
        ->set('school_name', 'Updated School')
        ->set('relationship_to_customer', 'Son')
        ->call('saveFamilyMember')
        ->assertHasNoErrors();

    expect($familyMember->refresh())
        ->name->toBe('Updated Name')
        ->school_name->toBe('Updated School')
        ->relationship_to_customer->toBe('Son');
});

test('customer can delete their family member from the panel action', function () {
    $customer = Customer::factory()->create();
    $familyMember = FamilyMember::factory()
        ->for($customer)
        ->create();

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::customer.family-members.index')
        ->call('deleteFamilyMember', $familyMember->id)
        ->assertHasNoErrors();

    $this->assertModelMissing($familyMember);
});

test('customer with incomplete profile cannot mutate family members from the panel', function () {
    $customer = Customer::factory()->incompleteProfile()->create();
    $familyMember = FamilyMember::factory()
        ->for($customer)
        ->create([
            'name' => 'Old Name',
            'school_name' => 'Old School',
        ]);

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::customer.family-members.index')
        ->set('name', 'Maha Al Harthy')
        ->set('birth_date', now()->subYears(12)->format('Y-m-d'))
        ->call('saveFamilyMember')
        ->assertHasErrors(['profile']);

    Livewire::test('pages::customer.family-members.index')
        ->call('editFamilyMember', $familyMember->id)
        ->set('name', 'Updated Name')
        ->call('saveFamilyMember')
        ->assertHasErrors(['profile']);

    Livewire::test('pages::customer.family-members.index')
        ->call('deleteFamilyMember', $familyMember->id)
        ->assertHasErrors(['profile']);

    expect(FamilyMember::query()->count())->toBe(1)
        ->and($familyMember->refresh()->name)->toBe('Old Name');
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
        ->get(route('customer.bookings.index'))
        ->assertOk()
        ->assertSee('BRH-10001')
        ->assertSee('Mountain Trip')
        ->assertSee(__('ui.actions.open'));
});

test('money component renders the omani rial symbol before omr amounts', function () {
    $this->blade('<x-money :amount-baisa="12500" currency="OMR" />')
        ->assertSee('data-omr-symbol', false)
        ->assertSee('12.500')
        ->assertSee('OMR');

    expect(MoneyFormatter::baisa(12500))->toBe('OMR 12.500');
});

test('payments page renders customer installments payment attempts and refunds', function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();
    $event = Event::factory()->create(['name' => 'Sea Camp']);
    $otherEvent = Event::factory()->create(['name' => 'Private Event']);
    $booking = Booking::factory()
        ->for($customer)
        ->for($event)
        ->create(['reference' => 'BRH-PAY-1']);
    $otherBooking = Booking::factory()
        ->for($otherCustomer)
        ->for($otherEvent)
        ->create(['reference' => 'BRH-OTHER']);

    $schedule = BookingPaymentSchedule::factory()->for($booking)->create([
        'plan_name' => 'Two payments',
        'currency' => 'OMR',
        'total_baisa' => 9000,
    ]);
    $firstInstallment = BookingInstallment::factory()->for($schedule, 'paymentSchedule')->create([
        'name' => 'Deposit',
        'sequence' => 1,
        'amount_baisa' => 4000,
        'state' => BookingInstallmentState::Paid,
        'paid_at' => now(),
    ]);
    BookingInstallment::factory()->for($schedule, 'paymentSchedule')->create([
        'name' => 'Balance',
        'sequence' => 2,
        'amount_baisa' => 5000,
        'state' => BookingInstallmentState::Pending,
    ]);
    $payment = Payment::factory()->for($firstInstallment, 'bookingInstallment')->create([
        'reference' => 'PAY-CUSTOMER',
        'amount_baisa' => 4000,
        'currency' => 'OMR',
        'state' => PaymentState::PartiallyRefunded,
        'paid_at' => now(),
    ]);
    PaymentRefund::factory()->for($payment)->create([
        'reference' => 'REF-CUSTOMER',
        'amount_baisa' => 1000,
        'currency' => 'OMR',
        'state' => PaymentRefundState::Succeeded,
    ]);

    $otherSchedule = BookingPaymentSchedule::factory()->for($otherBooking)->create(['plan_name' => 'Hidden plan']);
    $otherInstallment = BookingInstallment::factory()->for($otherSchedule, 'paymentSchedule')->create(['name' => 'Hidden installment']);
    Payment::factory()->for($otherInstallment, 'bookingInstallment')->create(['reference' => 'PAY-OTHER']);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.payments.index'))
        ->assertOk()
        ->assertSee('BRH-PAY-1')
        ->assertSee('Sea Camp')
        ->assertSee('Two payments')
        ->assertSee('Deposit')
        ->assertSee('Balance')
        ->assertSee('PAY-CUSTOMER')
        ->assertSee('REF-CUSTOMER')
        ->assertSee('hgi-wallet-02', false)
        ->assertSee('hgi-invoice-03', false)
        ->assertSee('data-status-color="green"', false)
        ->assertSee('data-status-color="amber"', false)
        ->assertSee('data-omr-symbol', false)
        ->assertSee('9.000')
        ->assertSee('1.000')
        ->assertDontSee('BRH-OTHER')
        ->assertDontSee('Private Event')
        ->assertDontSee('PAY-OTHER');
});

test('booking details page renders a compact contract overview for a single participant', function () {
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
    $contract = $booking->familyMembers()->firstOrFail()->contract()->firstOrFail();

    $this->actingAs($customer, 'customer')
        ->get(route('customer.bookings.show', $booking))
        ->assertOk()
        ->assertSee('BRH-20002')
        ->assertSee('Desert Camp')
        ->assertSee('Maha Al Harthy')
        ->assertSee(__('ui.actions.view_and_sign_contract'))
        ->assertSee(route('customer.bookings.contracts.show', [$booking, $contract]), false)
        ->assertDontSee('Safety terms must be reviewed before signature.')
        ->assertDontSee('<canvas', false)
        ->assertSee('hgi-stroke', false)
        ->assertSee('hgi-contracts', false);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.bookings.contracts.show', [$booking, $contract]))
        ->assertOk()
        ->assertSee(__('ui.bookings.contract_terms'))
        ->assertSee('Safety terms must be reviewed before signature.')
        ->assertSee(__('ui.actions.sign_contract'));
});
