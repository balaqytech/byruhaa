<?php

use App\Enums\PaymentState;
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
use App\States\Booking\Approved;
use App\States\Contract\Signed;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

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

test('customers can be searched by phone', function () {
    $matchingCustomer = Customer::factory()->create([
        'phone_number' => '+96891234567',
        'email' => 'phone-match@example.com',
    ]);
    Customer::factory()->create([
        'phone_number' => '+96892345678',
        'email' => 'other-phone@example.com',
    ]);

    $this->getJson('/api/v1/customers?phone=91234567')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchingCustomer->id);

    $this->getJson('/api/v1/customers?search=91234567')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchingCustomer->id);
});

test('customers can be searched by email', function () {
    $matchingCustomer = Customer::factory()->create([
        'email' => 'mona.search@example.com',
    ]);
    Customer::factory()->create([
        'email' => 'salim.search@example.com',
    ]);

    $this->getJson('/api/v1/customers?email=mona.search@example.com')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchingCustomer->id);

    $this->getJson('/api/v1/customers?search=mona.search@example.com')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $matchingCustomer->id);
});

test('customer profile completion validates required input', function () {
    $customer = Customer::factory()->incompleteProfile()->create();

    $this->patchJson("/api/v1/customers/{$customer->id}/profile", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'name',
            'phone_number',
            'civil_id',
            'address',
            'wilaya',
            'area',
        ]);
});

test('customer profile completion updates allowed fields', function () {
    $customer = Customer::factory()->incompleteProfile()->create();

    $this->patchJson("/api/v1/customers/{$customer->id}/profile", [
        'name' => 'Mona Profile',
        'email' => 'profile@example.com',
        'phone_number' => '91234567',
        'civil_id' => '12345678',
        'address' => 'House 12',
        'wilaya' => 'Muscat',
        'area' => 'Qurum',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Mona Profile')
        ->assertJsonPath('data.email', 'profile@example.com')
        ->assertJsonPath('data.phone_number', '+96891234567')
        ->assertJsonPath('data.profile_complete', true)
        ->assertJsonPath('data.missing_required_profile_fields', []);

    $customer->refresh();

    expect($customer->name)->toBe('Mona Profile')
        ->and($customer->email)->toBe('profile@example.com')
        ->and($customer->phone_number)->toBe('+96891234567')
        ->and($customer->civil_id)->toBe('12345678')
        ->and($customer->address)->toBe('House 12')
        ->and($customer->wilaya)->toBe('Muscat')
        ->and($customer->area)->toBe('Qurum');
});

test('customer profile completion rejects protected fields', function () {
    $customer = Customer::factory()->create([
        'name' => 'Original Customer',
        'additional_info' => ['language' => 'en'],
    ]);
    $originalPassword = $customer->password;

    $this->patchJson("/api/v1/customers/{$customer->id}/profile", [
        'name' => 'Changed Customer',
        'email' => 'changed@example.com',
        'phone_number' => $customer->phone_number,
        'civil_id' => $customer->civil_id,
        'address' => $customer->address,
        'wilaya' => $customer->wilaya,
        'area' => $customer->area,
        'additional_info' => ['language' => 'ar'],
        'password' => 'new-password',
        'status' => 'approved',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['additional_info', 'password', 'status']);

    $customer->refresh();

    expect($customer->name)->toBe('Original Customer')
        ->and($customer->additional_info)->toBe(['language' => 'en'])
        ->and($customer->password)->toBe($originalPassword);
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
        ->assertJsonPath('data.unit_price', '12.000')
        ->assertJsonPath('data.subtotal', '24.000')
        ->assertJsonPath('data.discount_amount', '0.000')
        ->assertJsonPath('data.total', '24.000')
        ->assertJsonPath('data.event.price', '12.000')
        ->assertJsonMissingPath('data.total_baisa');

    $this->assertStringNotContainsString('_baisa', $createResponse->getContent());

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
    $publishedEvent = Event::factory()->create([
        'name' => 'Published Camp',
        'price_baisa' => 12000,
    ]);
    $draftEvent = Event::factory()->draft()->create(['name' => 'Draft Camp']);

    $this->getJson('/api/v1/events')
        ->assertOk()
        ->assertJsonFragment(['name' => 'Published Camp'])
        ->assertJsonPath('data.0.price', '12.000')
        ->assertJsonMissingPath('data.0.price_baisa')
        ->assertJsonMissing(['name' => 'Draft Camp']);

    $this->getJson("/api/v1/events/{$publishedEvent->slug}")
        ->assertOk()
        ->assertJsonPath('data.id', $publishedEvent->id)
        ->assertJsonPath('data.price', '12.000');

    $this->getJson("/api/v1/events/{$draftEvent->slug}")
        ->assertNotFound();
});

test('event show includes available discounts and payment plans', function () {
    $event = Event::factory()->create([
        'price_baisa' => 12000,
    ]);
    Discount::factory()->for($event)->create([
        'name' => 'Sibling Discount',
        'amount_baisa' => 2500,
        'minimum_family_members' => 2,
    ]);
    Discount::factory()->create([
        'name' => 'Global Discount',
        'event_id' => null,
        'amount_baisa' => 1000,
    ]);
    Discount::factory()->for($event)->create([
        'name' => 'Expired Discount',
        'amount_baisa' => 5000,
        'ends_at' => now()->subDay(),
    ]);

    $paymentPlan = EventPaymentPlan::factory()->for($event)->create([
        'name' => 'Two payments',
    ]);
    EventPaymentPlan::factory()->for($event)->create([
        'name' => 'Inactive payments',
        'is_active' => false,
    ]);
    EventPaymentPlanInstallment::factory()->for($paymentPlan, 'paymentPlan')->create([
        'name' => 'Deposit',
        'sequence' => 1,
        'percentage' => 50,
        'due_date' => '2026-07-01',
    ]);
    EventPaymentPlanInstallment::factory()->for($paymentPlan, 'paymentPlan')->create([
        'name' => 'Final payment',
        'sequence' => 2,
        'percentage' => 50,
        'due_date' => '2026-08-01',
    ]);

    $this->getJson("/api/v1/events/{$event->slug}")
        ->assertOk()
        ->assertJsonPath('data.available_discounts.0.name', 'Sibling Discount')
        ->assertJsonPath('data.available_discounts.0.type', 'fixed_amount_per_family_member')
        ->assertJsonPath('data.available_discounts.0.value', '2.500')
        ->assertJsonPath('data.available_discounts.0.eligibility.minimum_family_members', 2)
        ->assertJsonPath('data.available_discounts.1.name', 'Global Discount')
        ->assertJsonPath('data.available_discounts.1.value', '1.000')
        ->assertJsonMissing(['name' => 'Expired Discount'])
        ->assertJsonPath('data.payment_plans.0.name', 'Two payments')
        ->assertJsonPath('data.payment_plans.0.installments_count', 2)
        ->assertJsonPath('data.payment_plans.0.installments.0.name', 'Deposit')
        ->assertJsonPath('data.payment_plans.0.installments.0.amount', '6.000')
        ->assertJsonPath('data.payment_plans.0.installments.1.name', 'Final payment')
        ->assertJsonPath('data.payment_plans.0.installments.1.amount', '6.000')
        ->assertJsonMissing(['name' => 'Inactive payments']);
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
        ->assertJsonPath('data.amount', '2.500')
        ->assertJsonPath('data.booking_installment.amount', '2.500')
        ->assertJsonMissingPath('data.amount_baisa');

    $this->assertStringNotContainsString('_baisa', $createResponse->getContent());

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
        ->assertJsonPath('data.amount', '3.000')
        ->assertJsonPath('data.state', PaymentState::Paid->value);

    $this->deleteJson("/api/v1/customers/{$customer->id}/payments/{$payment->id}")
        ->assertNoContent();

    $this->assertModelMissing($payment);
});

test('customer can initiate a full booking payment through the api', function () {
    apiV1ConfigureThawani();
    Http::preventStrayRequests();
    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/checkout/session' => Http::response([
            'success' => true,
            'data' => [
                'session_id' => 'checkout_api_full',
            ],
        ]),
    ]);

    [$customer, $booking] = apiV1PayableBookingFixture(amountBaisa: 12000);

    $response = $this->postJson("/api/v1/customers/{$customer->id}/bookings/{$booking->id}/payments");

    $response
        ->assertCreated()
        ->assertJsonPath('data.amount', '12.000')
        ->assertJsonPath('data.checkout_url', 'https://uatcheckout.thawani.om/pay/checkout_api_full?key=test_publishable_key')
        ->assertJsonPath('data.request_payload.products.0.unit_amount', '12.000')
        ->assertJsonPath('data.booking_installment.amount', '12.000')
        ->assertJsonMissingPath('data.amount_baisa');

    $schedule = $booking->refresh()->paymentSchedule()->with('installments.payments')->firstOrFail();
    $installment = $schedule->installments->first();

    expect($schedule->event_payment_plan_id)->toBeNull()
        ->and($schedule->total_baisa)->toBe(12000)
        ->and($installment)->not->toBeNull()
        ->and($installment->amount_baisa)->toBe(12000)
        ->and($installment->payments)->toHaveCount(1)
        ->and($installment->payments->first()->provider_session_id)->toBe('checkout_api_full');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://uatcheckout.thawani.om/api/v1/checkout/session'
        && $request['products'][0]['unit_amount'] === 12000);
});

test('customer can select a payment plan and initiate first installment through the api', function () {
    apiV1ConfigureThawani();
    Http::preventStrayRequests();
    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/checkout/session' => Http::response([
            'success' => true,
            'data' => [
                'session_id' => 'checkout_api_plan',
            ],
        ]),
    ]);

    [$customer, $booking] = apiV1PayableBookingFixture(amountBaisa: 12000);
    $paymentPlan = EventPaymentPlan::factory()->for($booking->event)->create([
        'name' => 'Two payments',
    ]);
    EventPaymentPlanInstallment::factory()->for($paymentPlan, 'paymentPlan')->create([
        'name' => 'Deposit',
        'sequence' => 1,
        'percentage' => 50,
        'due_date' => now()->toDateString(),
    ]);
    EventPaymentPlanInstallment::factory()->for($paymentPlan, 'paymentPlan')->create([
        'name' => 'Final',
        'sequence' => 2,
        'percentage' => 50,
        'due_date' => now()->addMonth()->toDateString(),
    ]);

    $response = $this->postJson("/api/v1/customers/{$customer->id}/bookings/{$booking->id}/payments", [
        'payment_plan_id' => $paymentPlan->id,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.amount', '6.000')
        ->assertJsonPath('data.checkout_url', 'https://uatcheckout.thawani.om/pay/checkout_api_plan?key=test_publishable_key')
        ->assertJsonPath('data.request_payload.products.0.unit_amount', '6.000')
        ->assertJsonPath('data.booking_installment.name', 'Deposit')
        ->assertJsonPath('data.booking_installment.amount', '6.000');

    $schedule = $booking->refresh()->paymentSchedule()->with('installments.payments')->firstOrFail();

    expect($schedule->event_payment_plan_id)->toBe($paymentPlan->id)
        ->and($schedule->installments)->toHaveCount(2)
        ->and($schedule->installments[0]->amount_baisa)->toBe(6000)
        ->and($schedule->installments[1]->amount_baisa)->toBe(6000)
        ->and($schedule->installments[0]->payments)->toHaveCount(1);
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

function apiV1ConfigureThawani(): void
{
    config([
        'payments.default' => 'thawani',
        'thawani.mode' => 'test',
        'thawani.test.secret_key' => 'test_secret_key',
        'thawani.test.publishable_key' => 'test_publishable_key',
        'thawani.test.base_url' => 'https://uatcheckout.thawani.om/api/v1',
        'thawani.test.checkout_base_url' => 'https://uatcheckout.thawani.om/pay',
    ]);
}

/**
 * @return array{0: Customer, 1: Booking}
 */
function apiV1PayableBookingFixture(int $amountBaisa): array
{
    $customer = Customer::factory()->create();
    $event = Event::factory()->create(['name' => 'API Payment Camp']);
    $booking = Booking::factory()->for($customer)->for($event)->create([
        'state' => Approved::$name,
        'currency' => 'OMR',
        'subtotal_baisa' => $amountBaisa,
        'total_baisa' => $amountBaisa,
    ]);
    $familyMember = FamilyMember::factory()->for($customer)->create();
    $bookingFamilyMember = BookingFamilyMember::factory()->for($booking)->for($familyMember)->create();

    EventContract::factory()->for($bookingFamilyMember, 'bookingFamilyMember')->create([
        'state' => Signed::$name,
        'signed_at' => now(),
    ]);

    return [$customer, $booking];
}
