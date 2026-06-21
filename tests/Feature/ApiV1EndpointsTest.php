<?php

use App\Enums\PaymentState;
use App\Models\Booking;
use App\Models\BookingInstallment;
use App\Models\BookingPaymentSchedule;
use App\Models\Customer;
use App\Models\Event;
use App\Models\FamilyMember;
use App\Models\Payment;

test('customers can be managed through the api and require a phone number', function () {
    $this->postJson('/api/v1/customers', [
        'name' => 'Mona Said',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('phone_number');

    $createResponse = $this->postJson('/api/v1/customers', [
        'name' => 'Mona Said',
        'email' => 'mona@example.com',
        'phone_number' => '91234567',
        'civil_id' => '12345678',
        'address' => 'House 12',
        'wilaya' => 'Muscat',
        'area' => 'Qurum',
        'additional_info' => [
            'preferred_language' => 'en',
        ],
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('data.name', 'Mona Said')
        ->assertJsonPath('data.phone_number', '+96891234567')
        ->assertJsonPath('data.civil_id', '12345678')
        ->assertJsonPath('data.address', 'House 12')
        ->assertJsonPath('data.wilaya', 'Muscat')
        ->assertJsonPath('data.area', 'Qurum')
        ->assertJsonPath('data.additional_info.preferred_language', 'en')
        ->assertJsonPath('data.profile_complete', true)
        ->assertJsonPath('data.missing_required_profile_fields', []);

    $customer = Customer::firstOrFail();

    $this->getJson('/api/v1/customers')
        ->assertOk()
        ->assertJsonPath('data.0.id', $customer->id);

    $this->getJson("/api/v1/customers/{$customer->id}")
        ->assertOk()
        ->assertJsonPath('data.email', 'mona@example.com');

    $this->patchJson("/api/v1/customers/{$customer->id}", [
        'name' => 'Mona Al Said',
        'phone_number' => '92345678',
        'area' => '',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Mona Al Said')
        ->assertJsonPath('data.phone_number', '+96892345678')
        ->assertJsonPath('data.area', null)
        ->assertJsonPath('data.profile_complete', false)
        ->assertJsonPath('data.missing_required_profile_fields', ['area']);

    $this->deleteJson("/api/v1/customers/{$customer->id}")
        ->assertNoContent();

    $this->assertModelMissing($customer);
});

test('customer bookings can be created listed and shown', function () {
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'price_baisa' => 12000,
        'seat_capacity' => 10,
    ]);
    $familyMembers = FamilyMember::factory()->count(2)->for($customer)->create();

    $createResponse = $this->postJson("/api/v1/customers/{$customer->id}/bookings", [
        'event_id' => $event->id,
        'family_member_ids' => $familyMembers->pluck('id')->all(),
    ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('data.customer_id', $customer->id)
        ->assertJsonPath('data.event_id', $event->id)
        ->assertJsonPath('data.family_member_count', 2)
        ->assertJsonPath('data.total_baisa', 24000);

    $booking = Booking::firstOrFail();

    $this->getJson("/api/v1/customers/{$customer->id}/bookings")
        ->assertOk()
        ->assertJsonPath('data.0.id', $booking->id);

    $this->getJson("/api/v1/customers/{$customer->id}/bookings/{$booking->id}")
        ->assertOk()
        ->assertJsonPath('data.reference', $booking->reference);
});

test('customer with incomplete profile cannot create a booking through the api', function () {
    $customer = Customer::factory()->incompleteProfile()->create();
    $event = Event::factory()->create([
        'price_baisa' => 12000,
        'seat_capacity' => 10,
    ]);
    $familyMembers = FamilyMember::factory()->count(2)->for($customer)->create();

    $this->postJson("/api/v1/customers/{$customer->id}/bookings", [
        'event_id' => $event->id,
        'family_member_ids' => $familyMembers->pluck('id')->all(),
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('family_member_ids');

    expect(Booking::query()->count())->toBe(0);
});

test('only available events can be listed and shown', function () {
    $publishedEvent = Event::factory()->create(['name' => 'Published Camp']);
    $draftEvent = Event::factory()->draft()->create(['name' => 'Draft Camp']);

    $this->getJson('/api/v1/events')
        ->assertOk()
        ->assertJsonFragment(['name' => 'Published Camp'])
        ->assertJsonMissing(['name' => 'Draft Camp']);

    $this->getJson("/api/v1/events/{$publishedEvent->slug}")
        ->assertOk()
        ->assertJsonPath('data.id', $publishedEvent->id);

    $this->getJson("/api/v1/events/{$draftEvent->slug}")
        ->assertNotFound();
});

test('customer payments can be managed through the api', function () {
    $customer = Customer::factory()->create();
    $event = Event::factory()->create();
    $booking = Booking::factory()->for($customer)->for($event)->create();
    $schedule = BookingPaymentSchedule::factory()->for($booking)->create([
        'event_payment_plan_id' => null,
        'plan_name' => 'Full payment',
    ]);
    $installment = BookingInstallment::factory()->for($schedule, 'paymentSchedule')->create([
        'amount_baisa' => 2500,
    ]);

    $createResponse = $this->postJson("/api/v1/customers/{$customer->id}/payments", [
        'booking_installment_id' => $installment->id,
        'amount_baisa' => 2500,
        'state' => PaymentState::Pending->value,
    ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('data.booking_installment_id', $installment->id)
        ->assertJsonPath('data.amount_baisa', 2500);

    $payment = Payment::firstOrFail();

    $this->getJson("/api/v1/customers/{$customer->id}/payments")
        ->assertOk()
        ->assertJsonPath('data.0.id', $payment->id);

    $this->getJson("/api/v1/customers/{$customer->id}/payments/{$payment->id}")
        ->assertOk()
        ->assertJsonPath('data.reference', $payment->reference);

    $this->patchJson("/api/v1/customers/{$customer->id}/payments/{$payment->id}", [
        'amount_baisa' => 3000,
        'state' => PaymentState::Paid->value,
    ])
        ->assertOk()
        ->assertJsonPath('data.amount_baisa', 3000)
        ->assertJsonPath('data.state', PaymentState::Paid->value);

    $this->deleteJson("/api/v1/customers/{$customer->id}/payments/{$payment->id}")
        ->assertNoContent();

    $this->assertModelMissing($payment);
});

test('customer with incomplete profile cannot mutate payments through the api', function () {
    $customer = Customer::factory()->incompleteProfile()->create();
    $event = Event::factory()->create();
    $booking = Booking::factory()->for($customer)->for($event)->create();
    $schedule = BookingPaymentSchedule::factory()->for($booking)->create([
        'event_payment_plan_id' => null,
        'plan_name' => 'Full payment',
    ]);
    $installment = BookingInstallment::factory()->for($schedule, 'paymentSchedule')->create([
        'amount_baisa' => 2500,
    ]);
    $payment = Payment::factory()->for($installment, 'bookingInstallment')->create([
        'amount_baisa' => 2500,
        'state' => PaymentState::Pending,
    ]);

    $this->postJson("/api/v1/customers/{$customer->id}/payments", [
        'booking_installment_id' => $installment->id,
        'amount_baisa' => 2500,
        'state' => PaymentState::Pending->value,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('profile');

    $this->patchJson("/api/v1/customers/{$customer->id}/payments/{$payment->id}", [
        'amount_baisa' => 3000,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('profile');

    $this->deleteJson("/api/v1/customers/{$customer->id}/payments/{$payment->id}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('profile');

    expect(Payment::query()->count())->toBe(1)
        ->and($payment->refresh()->amount_baisa)->toBe(2500);
});

test('customer family members can be managed through the api', function () {
    $customer = Customer::factory()->create();

    $createResponse = $this->postJson("/api/v1/customers/{$customer->id}/family-members", [
        'name' => 'Salim Said',
        'birth_date' => '2014-01-01',
        'school_name' => 'Muscat School',
        'grade' => '6',
        'relationship_to_customer' => 'Son',
    ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('data.customer_id', $customer->id)
        ->assertJsonPath('data.name', 'Salim Said')
        ->assertJsonPath('data.relationship_to_customer', 'Son');

    $familyMember = FamilyMember::firstOrFail();

    $this->getJson("/api/v1/customers/{$customer->id}/family-members")
        ->assertOk()
        ->assertJsonPath('data.0.id', $familyMember->id);

    $this->getJson("/api/v1/customers/{$customer->id}/family-members/{$familyMember->id}")
        ->assertOk()
        ->assertJsonPath('data.birth_date', '2014-01-01');

    $this->patchJson("/api/v1/customers/{$customer->id}/family-members/{$familyMember->id}", [
        'grade' => '7',
        'medical_notes' => 'Peanut allergy',
        'relationship_to_customer' => 'Brother',
    ])
        ->assertOk()
        ->assertJsonPath('data.grade', '7')
        ->assertJsonPath('data.medical_notes', 'Peanut allergy')
        ->assertJsonPath('data.relationship_to_customer', 'Brother');

    $this->deleteJson("/api/v1/customers/{$customer->id}/family-members/{$familyMember->id}")
        ->assertNoContent();

    $this->assertModelMissing($familyMember);
});

test('customer with incomplete profile cannot mutate family members through the api', function () {
    $customer = Customer::factory()->incompleteProfile()->create();
    $familyMember = FamilyMember::factory()->for($customer)->create([
        'grade' => '6',
    ]);

    $this->postJson("/api/v1/customers/{$customer->id}/family-members", [
        'name' => 'Salim Said',
        'birth_date' => '2014-01-01',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('profile');

    $this->patchJson("/api/v1/customers/{$customer->id}/family-members/{$familyMember->id}", [
        'grade' => '7',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('profile');

    $this->deleteJson("/api/v1/customers/{$customer->id}/family-members/{$familyMember->id}")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('profile');

    expect(FamilyMember::query()->count())->toBe(1)
        ->and($familyMember->refresh()->grade)->toBe('6');
});
