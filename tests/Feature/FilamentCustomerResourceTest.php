<?php

use App\Enums\BookingInstallmentState;
use App\Enums\PaymentRefundState;
use App\Enums\PaymentState;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Bookings\Pages\ViewBooking;
use App\Filament\Resources\Bookings\RelationManagers\FamilyMembersRelationManager as BookingFamilyMembersRelationManager;
use App\Filament\Resources\Bookings\RelationManagers\InstallmentsRelationManager as BookingInstallmentsRelationManager;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\RelationManagers\BookingsRelationManager as CustomerBookingsRelationManager;
use App\Filament\Resources\Customers\RelationManagers\FamilyMembersRelationManager as CustomerFamilyMembersRelationManager;
use App\Filament\Resources\Discounts\DiscountResource;
use App\Filament\Resources\Discounts\Pages\CreateDiscount;
use App\Filament\Resources\EventPaymentPlans\EventPaymentPlanResource;
use App\Filament\Resources\EventPaymentPlans\Pages\CreateEventPaymentPlan;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Filament\Resources\Events\Pages\ViewEvent;
use App\Filament\Resources\Events\RelationManagers\BookingsRelationManager as EventBookingsRelationManager;
use App\Filament\Resources\Events\RelationManagers\DiscountsRelationManager as EventDiscountsRelationManager;
use App\Filament\Resources\Events\RelationManagers\PaymentPlansRelationManager as EventPaymentPlansRelationManager;
use App\Models\Booking;
use App\Models\BookingFamilyMember;
use App\Models\BookingInstallment;
use App\Models\BookingPaymentSchedule;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Event;
use App\Models\EventContract;
use App\Models\EventPaymentPlan;
use App\Models\EventPaymentPlanInstallment;
use App\Models\FamilyMember;
use App\Models\Payment;
use App\Models\PaymentRefund;
use App\Models\User;
use App\States\Booking\Approved;
use App\States\Contract\Signed;
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

test('staff can view a booking with members contracts payments and refunds in filament', function () {
    $staff = User::factory()->create();
    $reviewer = User::factory()->create(['name' => 'Review Staff']);
    $customer = Customer::factory()->create([
        'name' => 'Aisha Guardian',
        'email' => 'guardian@example.test',
        'phone_number' => '+96891234567',
    ]);
    $event = Event::factory()->create([
        'name' => 'Sea Camp',
        'location' => 'Muscat',
        'type' => 'camp',
    ]);
    $booking = Booking::factory()
        ->for($customer)
        ->for($event)
        ->create([
            'reference' => 'BRH-VIEW-1',
            'state' => Approved::$name,
            'reviewed_by_user_id' => $reviewer->id,
            'reviewed_at' => '2026-06-20 09:00:00',
            'review_notes' => 'Approved after document check.',
            'unit_price_baisa' => 6000,
            'family_member_count' => 1,
            'subtotal_baisa' => 6000,
            'discount_name' => 'Sibling discount',
            'discount_amount_baisa' => 1000,
            'total_baisa' => 5000,
        ]);
    $familyMember = FamilyMember::factory()
        ->for($customer)
        ->create([
            'name' => 'Maha Student',
            'school_name' => 'Future School',
            'grade' => '6',
        ]);
    $bookingFamilyMember = BookingFamilyMember::factory()
        ->for($booking)
        ->for($familyMember)
        ->create();

    EventContract::factory()
        ->for($bookingFamilyMember)
        ->create([
            'state' => Signed::$name,
            'signed_name' => 'Aisha Guardian',
            'signed_at' => '2026-06-20 10:00:00',
        ]);

    $schedule = BookingPaymentSchedule::factory()
        ->for($booking)
        ->create([
            'plan_name' => 'Two payments',
            'currency' => 'OMR',
            'subtotal_baisa' => 6000,
            'discount_amount_baisa' => 1000,
            'total_baisa' => 5000,
        ]);
    $installment = BookingInstallment::factory()
        ->for($schedule, 'paymentSchedule')
        ->create([
            'name' => 'Deposit',
            'percentage' => 50,
            'due_date' => '2026-07-01',
            'amount_baisa' => 2500,
            'state' => BookingInstallmentState::Paid,
            'paid_at' => '2026-06-20 11:00:00',
        ]);
    $payment = Payment::factory()
        ->for($installment, 'bookingInstallment')
        ->create([
            'reference' => 'PAY-VIEW-1',
            'provider' => 'thawani',
            'amount_baisa' => 2500,
            'currency' => 'OMR',
            'state' => PaymentState::PartiallyRefunded,
            'provider_payment_status' => 'paid',
            'verified_at' => '2026-06-20 11:05:00',
            'paid_at' => '2026-06-20 11:05:00',
        ]);

    PaymentRefund::factory()
        ->for($payment)
        ->create([
            'reference' => 'REF-VIEW-1',
            'amount_baisa' => 500,
            'currency' => 'OMR',
            'state' => PaymentRefundState::Succeeded,
            'reason' => 'Partial adjustment',
            'processed_at' => '2026-06-20 12:00:00',
        ]);

    $this->actingAs($staff, 'web')
        ->get(BookingResource::getUrl('view', ['record' => $booking]))
        ->assertOk()
        ->assertSee('BRH-VIEW-1')
        ->assertSee('Aisha Guardian')
        ->assertSee('guardian@example.test')
        ->assertSee('Sea Camp')
        ->assertSee('Review Staff')
        ->assertSee('Approved after document check.')
        ->assertSee('Sibling discount')
        ->assertSee('OMR 5.000')
        ->assertSee('Family members')
        ->assertSee('Installments');

    $this->actingAs($staff, 'web')
        ->get(BookingResource::getUrl('edit', ['record' => $booking]))
        ->assertOk()
        ->assertSee(__('admin.resources.bookings.label'))
        ->assertSee('Family members')
        ->assertSee('Installments');

    Livewire::test(BookingFamilyMembersRelationManager::class, [
        'ownerRecord' => $booking,
        'pageClass' => ViewBooking::class,
    ])
        ->assertCanSeeTableRecords([$bookingFamilyMember])
        ->assertSee('Maha Student')
        ->assertSee('Future School')
        ->assertSee(__('admin.statuses.signed'))
        ->assertTableHeaderActionsExistInOrder([])
        ->assertTableActionsExistInOrder([]);

    Livewire::test(BookingInstallmentsRelationManager::class, [
        'ownerRecord' => $booking,
        'pageClass' => ViewBooking::class,
    ])
        ->assertCanSeeTableRecords([$installment])
        ->assertSee('Deposit')
        ->assertSee('OMR 2.500')
        ->assertTableHeaderActionsExistInOrder([])
        ->assertTableActionsExistInOrder([]);
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

test('staff can use rich editor merge tags for contract variables in event form', function () {
    $staff = User::factory()->create();

    $this->actingAs($staff, 'web')
        ->get(EventResource::getUrl('create'))
        ->assertOk()
        ->assertSee('guardian_name', false)
        ->assertSee('student_name', false)
        ->assertSee('agreed_fee', false)
        ->assertSee(__('admin.contract_variables.labels.guardian_name'))
        ->assertDontSee('Contract variables');
});

test('staff can save participant extra fields on an event', function () {
    $staff = User::factory()->create();

    $this->actingAs($staff, 'web');

    Livewire::test(CreateEvent::class)
        ->fillForm([
            'name' => 'Participant Camp',
            'slug' => 'participant-camp',
            'type' => 'camp',
            'status' => 'published',
            'location' => 'Muscat',
            'seat_capacity' => 20,
            'price' => '12.000',
            'currency' => 'OMR',
            'minimum_age' => 9,
            'maximum_age' => 16,
            'starts_at' => '2026-07-01 09:00:00',
            'ends_at' => '2026-07-01 15:00:00',
            'excerpt' => 'A concise public summary.',
            'description_html' => '<p>Detailed public content.</p>',
            'contract_terms_html' => '<p>Participant contract terms.</p>',
            'participant_extra_fields' => [
                [
                    'key' => 'swimming_level',
                    'label' => 'Swimming level',
                    'type' => 'select',
                    'required' => true,
                    'options' => "Beginner\nAdvanced",
                    'placeholder' => 'Choose level',
                    'help_text' => 'Used for group assignment.',
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $event = Event::query()->where('slug', 'participant-camp')->firstOrFail();

    expect($event->participant_extra_fields)
        ->toHaveCount(1)
        ->and($event->participant_extra_fields[0]['key'])->toBe('swimming_level')
        ->and($event->participant_extra_fields[0]['required'])->toBeTrue()
        ->and($event->price_baisa)->toBe(12000)
        ->and($event->location)->toBe('Muscat')
        ->and($event->excerpt)->toBe('A concise public summary.')
        ->and($event->description_html)->toContain('Detailed public content')
        ->and($event->contract_terms_html)->toContain('Participant contract terms');
});

test('staff can edit all event wizard fields including price and participant fields', function () {
    $staff = User::factory()->create();
    $event = Event::factory()->create([
        'slug' => 'editable-event',
        'price_baisa' => 5000,
    ]);

    $this->actingAs($staff, 'web');

    Livewire::test(EditEvent::class, ['record' => $event->getRouteKey()])
        ->fillForm([
            'name' => 'Edited Participant Camp',
            'slug' => 'edited-participant-camp',
            'type' => 'festival',
            'status' => 'draft',
            'location' => 'Salalah',
            'seat_capacity' => 25,
            'price' => '10.500',
            'currency' => 'OMR',
            'minimum_age' => 10,
            'maximum_age' => 17,
            'starts_at' => '2026-08-01 09:00:00',
            'ends_at' => '2026-08-03 15:00:00',
            'excerpt' => 'Updated public summary.',
            'description_html' => '<p>Updated detailed content.</p>',
            'contract_terms_html' => '<p>Updated contract terms.</p>',
            'participant_extra_fields' => [
                [
                    'key' => 'shirt_size',
                    'label' => 'Shirt size',
                    'type' => 'text',
                    'required' => false,
                    'placeholder' => 'Size',
                    'help_text' => 'Used for kit preparation.',
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $event->refresh();

    expect($event->name)->toBe('Edited Participant Camp')
        ->and($event->type->value)->toBe('festival')
        ->and($event->status->value)->toBe('draft')
        ->and($event->price_baisa)->toBe(10500)
        ->and($event->participant_extra_fields)->toHaveCount(1)
        ->and($event->participant_extra_fields[0]['key'])->toBe('shirt_size');
});

test('event view page combines infolist and relation manager tabs', function () {
    $staff = User::factory()->create();
    $event = Event::factory()->create(['name' => 'Tabbed Event']);
    $discount = Discount::factory()->for($event)->create(['name' => 'Tabbed Discount']);
    $paymentPlan = EventPaymentPlan::factory()->for($event)->create(['name' => 'Tabbed Plan']);
    $booking = Booking::factory()->for($event)->create(['reference' => 'BRH-EVENT-TAB']);

    $this->actingAs($staff, 'web')
        ->get(EventResource::getUrl('view', ['record' => $event]))
        ->assertOk()
        ->assertSee(__('admin.resources.events.label'))
        ->assertSee(__('admin.event_relation_managers.discounts.label'))
        ->assertSee(__('admin.event_relation_managers.payment_plans.label'))
        ->assertSee(__('admin.event_relation_managers.bookings.label'));

    Livewire::test(EventDiscountsRelationManager::class, [
        'ownerRecord' => $event,
        'pageClass' => ViewEvent::class,
    ])
        ->assertCanSeeTableRecords([$discount]);

    Livewire::test(EventPaymentPlansRelationManager::class, [
        'ownerRecord' => $event,
        'pageClass' => ViewEvent::class,
    ])
        ->assertCanSeeTableRecords([$paymentPlan]);

    Livewire::test(EventBookingsRelationManager::class, [
        'ownerRecord' => $event,
        'pageClass' => ViewEvent::class,
    ])
        ->assertCanSeeTableRecords([$booking]);
});

test('customer edit page combines form and relation manager tabs', function () {
    $staff = User::factory()->create();
    $customer = Customer::factory()->create(['name' => 'Tabbed Customer']);
    $familyMember = FamilyMember::factory()->for($customer)->create(['name' => 'Tabbed Student']);
    $booking = Booking::factory()->for($customer)->create(['reference' => 'BRH-CUSTOMER-TAB']);

    $this->actingAs($staff, 'web')
        ->get(CustomerResource::getUrl('edit', ['record' => $customer]))
        ->assertOk()
        ->assertSee(__('admin.resources.customers.label'))
        ->assertSee('Family members')
        ->assertSee('Bookings');

    Livewire::test(CustomerFamilyMembersRelationManager::class, [
        'ownerRecord' => $customer,
        'pageClass' => EditCustomer::class,
    ])
        ->assertCanSeeTableRecords([$familyMember])
        ->assertTableHeaderActionsExistInOrder(['create'])
        ->assertTableActionsExistInOrder(['edit', 'delete']);

    Livewire::test(CustomerBookingsRelationManager::class, [
        'ownerRecord' => $customer,
        'pageClass' => EditCustomer::class,
    ])
        ->assertCanSeeTableRecords([$booking])
        ->assertTableHeaderActionsExistInOrder([])
        ->assertTableActionsExistInOrder(['view']);
});

test('staff can view discounts in filament', function () {
    $staff = User::factory()->create();
    $discount = Discount::factory()->create([
        'name' => 'Family per-member discount',
        'amount_baisa' => 2500,
    ]);

    $this->actingAs($staff, 'web')
        ->get(DiscountResource::getUrl('index'))
        ->assertOk()
        ->assertSee($discount->name)
        ->assertSee(__('admin.fields.discount_amount_per_member'))
        ->assertSee('fi-ta-cell-amount-baisa', false);
});

test('staff can save discount amount as omr in filament', function () {
    $staff = User::factory()->create();

    $this->actingAs($staff, 'web');

    Livewire::test(CreateDiscount::class)
        ->fillForm([
            'name' => 'Filament OMR discount',
            'amount' => '2.500',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $discount = Discount::query()->where('name', 'Filament OMR discount')->firstOrFail();

    expect($discount->amount_baisa)->toBe(2500)
        ->and($discount->currency)->toBe('OMR');
});

test('staff must enter a valid discount date and family member range', function () {
    $staff = User::factory()->create();

    $this->actingAs($staff, 'web');

    Livewire::test(CreateDiscount::class)
        ->fillForm([
            'name' => 'Invalid discount',
            'amount' => '2.500',
            'starts_at' => '2026-08-01 00:00:00',
            'ends_at' => '2026-07-01 00:00:00',
            'minimum_family_members' => 5,
            'maximum_family_members' => 2,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'ends_at',
            'maximum_family_members',
        ]);
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
