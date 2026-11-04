<?php

use App\Enums\PaymentProvider;
use App\Enums\PaymentState;
use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\BookingFamilyMember;
use App\Modules\Events\Models\BookingInstallment;
use App\Modules\Events\Models\BookingPaymentSchedule;
use App\Modules\Events\Models\Discount;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventContract;
use App\Modules\Events\Models\EventPaymentPlan;
use App\Modules\Events\Models\EventPaymentPlanInstallment;
use App\Modules\Events\States\Booking\Approved;
use App\Modules\Events\States\Contract\Signed;
use App\Modules\Finance\Models\Payment;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function (): void {
    config([
        'byruhaa.uchat.api_token' => 'legacy-api-test-token',
        'byruhaa.uchat.owner_key_secret' => 'legacy-api-test-owner-secret',
    ]);
    $this->withToken('legacy-api-test-token');
});

test('customers can be managed through the api and require a phone number', function () {
    $this->postJson('/api/v1/integrations/uchat/account', ['name' => 'Mona', 'password' => 'password', 'password_confirmation' => 'password'])->assertUnprocessable()->assertJsonValidationErrors('uchat_phone');
    $this->withHeader('X-WhatsApp-Phone', '91234567')->postJson('/api/v1/integrations/uchat/account', [
        'name' => 'Mona Said', 'email' => 'mona@example.com', 'civil_id' => '12345678', 'address' => 'House 12', 'wilaya' => 'Muscat', 'area' => 'Qurum', 'password' => 'password', 'password_confirmation' => 'password',
    ])->assertCreated()->assertJsonPath('data.phone_number', '+96891234567')->assertJsonPath('data.profile_complete', true);
    $customer = Customer::query()->sole();
    $this->getJson('/api/v1/integrations/uchat/account')->assertOk()->assertJsonPath('data.id', $customer->id);
    $this->patchJson('/api/v1/integrations/uchat/account', ['name' => 'Mona Al Said', 'area' => null])->assertOk()->assertJsonPath('data.name', 'Mona Al Said')->assertJsonPath('data.profile_complete', false);
    $this->postJson('/api/v1/integrations/uchat/account/deletion-requests')->assertAccepted();
    $this->assertModelExists($customer);
});

test('customers can be searched by phone', function () {
    $matchingCustomer = Customer::factory()->create(['phone_number' => '+96891234567']);
    Customer::factory()->create(['phone_number' => '+96892345678']);
    $this->withHeader('X-WhatsApp-Phone', '91234567')->getJson('/api/v1/integrations/uchat/account')->assertOk()->assertJsonPath('data.id', $matchingCustomer->id);
    $this->getJson('/api/v1/customers?phone=92345678')->assertNotFound();
});

test('customers can be searched by email', function () {
    Customer::factory()->create(['email' => 'other@example.com']);
    $this->withHeader('X-WhatsApp-Phone', '91234567')->getJson('/api/v1/customers?email=other@example.com')->assertNotFound();
    $this->getJson('/api/v1/integrations/uchat/account')->assertConflict()->assertJsonPath('code', 'customer_account_required');
});

test('customer profile completion validates required input', function () {
    $customer = Customer::factory()->incompleteProfile()->create();
    $this->withHeader('X-WhatsApp-Phone', $customer->phone_number)->patchJson('/api/v1/integrations/uchat/account', ['name' => null, 'email' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email']);
});

test('customer profile completion updates allowed fields', function () {
    $customer = Customer::factory()->incompleteProfile()->create();
    $this->withHeader('X-WhatsApp-Phone', $customer->phone_number)->patchJson('/api/v1/integrations/uchat/account', [
        'name' => 'Mona Profile', 'email' => 'profile@example.com', 'civil_id' => '12345678', 'address' => 'House 12', 'wilaya' => 'Muscat', 'area' => 'Qurum',
    ])->assertOk()->assertJsonPath('data.profile_complete', true)->assertJsonPath('data.phone_number', $customer->phone_number);
    expect($customer->fresh()->name)->toBe('Mona Profile');
});

test('customer profile completion rejects protected fields', function () {
    $customer = Customer::factory()->create();
    $password = $customer->password;
    $this->withHeader('X-WhatsApp-Phone', $customer->phone_number)->patchJson('/api/v1/integrations/uchat/account', [
        'password' => 'changed', 'additional_info' => ['role' => 'admin'], 'phone_verified_at' => now()->toJSON(),
    ])->assertUnprocessable()->assertJsonValidationErrors(['password', 'additional_info', 'phone_verified_at']);
    expect($customer->fresh()->password)->toBe($password);
});

test('customer bookings can be created listed and shown', function () {
    config(['byruhaa.approval_mechanism' => 'manual']);

    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'price_baisa' => 12000,
        'seat_capacity' => 10,
    ]);
    $familyMembers = FamilyMember::factory()->count(2)->for($customer)->create();

    $createResponse = $this->withHeader('X-WhatsApp-Phone', $customer->phone_number)->withHeader('Idempotency-Key', (string) Str::uuid())->postJson('/api/v1/integrations/uchat/bookings', [
        'event_id' => $event->id,
        'family_member_ids' => $familyMembers->pluck('id')->all(),
    ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('data.customer_id', $customer->id)
        ->assertJsonPath('data.event_id', $event->id)
        ->assertJsonPath('data.state', 'pending_review')
        ->assertJsonPath('data.family_member_count', 2)
        ->assertJsonPath('data.unit_price_baisa', 12000)
        ->assertJsonPath('data.subtotal_baisa', 24000)
        ->assertJsonPath('data.discount_amount_baisa', 0)
        ->assertJsonPath('data.total_baisa', 24000)
        ->assertJsonPath('data.event.price_baisa', 12000)
        ->assertJsonMissingPath('data.total');

    $this->assertStringContainsString('_baisa', $createResponse->getContent());

    $booking = Booking::firstOrFail();

    expect($booking->state->getValue())->toBe('pending_review')
        ->and(EventContract::query()->count())->toBe(0);

    $this->withHeader('X-WhatsApp-Phone', $customer->phone_number)->withHeader('Idempotency-Key', (string) Str::uuid())->getJson('/api/v1/integrations/uchat/bookings')
        ->assertOk()
        ->assertJsonPath('data.0.id', $booking->id);

    $this->withHeader('X-WhatsApp-Phone', $customer->phone_number)->withHeader('Idempotency-Key', (string) Str::uuid())->getJson("/api/v1/integrations/uchat/bookings/{$booking->reference}")
        ->assertOk()
        ->assertJsonPath('data.reference', $booking->reference);
});

test('customer booking is approved automatically when configured', function () {
    config(['byruhaa.approval_mechanism' => 'auto']);

    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'price_baisa' => 12000,
        'seat_capacity' => 10,
    ]);
    $familyMembers = FamilyMember::factory()->count(2)->for($customer)->create();

    $createResponse = $this->withHeader('X-WhatsApp-Phone', $customer->phone_number)->withHeader('Idempotency-Key', (string) Str::uuid())->postJson('/api/v1/integrations/uchat/bookings', [
        'event_id' => $event->id,
        'family_member_ids' => $familyMembers->pluck('id')->all(),
    ]);

    $createResponse
        ->assertCreated()
        ->assertJsonPath('data.state', 'approved')
        ->assertJsonPath('data.reviewed_by_user_id', null);

    $booking = Booking::query()->with('familyMembers.contract')->firstOrFail();

    expect($booking->state)->toBeInstanceOf(Approved::class)
        ->and($booking->reviewed_by_user_id)->toBeNull()
        ->and($booking->reviewed_at)->not->toBeNull()
        ->and(EventContract::query()->count())->toBe(2)
        ->and($booking->familyMembers->every(fn (BookingFamilyMember $familyMember): bool => $familyMember->contract !== null))->toBeTrue();
});

test('customer with incomplete profile cannot create a booking through the api', function () {
    $customer = Customer::factory()->incompleteProfile()->create();
    $event = Event::factory()->create([
        'price_baisa' => 12000,
        'seat_capacity' => 10,
    ]);
    $familyMembers = FamilyMember::factory()->count(2)->for($customer)->create();

    $this->withHeader('X-WhatsApp-Phone', $customer->phone_number)->withHeader('Idempotency-Key', (string) Str::uuid())->postJson('/api/v1/integrations/uchat/bookings', [
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
    $booking = Booking::factory()->for($customer)->create();
    $schedule = BookingPaymentSchedule::factory()->for($booking)->create();
    $installment = BookingInstallment::factory()->for($schedule, 'paymentSchedule')->create(['amount_baisa' => 2500]);
    $payment = Payment::factory()->for($installment, 'bookingInstallment')->create(['amount_baisa' => 2500]);
    $this->withHeader('X-WhatsApp-Phone', $customer->phone_number)->getJson('/api/v1/integrations/uchat/payments')->assertOk()->assertJsonPath('data.0.amount_baisa', 2500);
    $this->getJson('/api/v1/integrations/uchat/payments/'.$payment->reference)->assertOk()->assertJsonMissingPath('data.request_payload')->assertJsonMissingPath('data.provider_session_id');
    $this->postJson('/api/v1/integrations/uchat/payments', ['state' => 'paid'])->assertMethodNotAllowed();
    $this->patchJson('/api/v1/integrations/uchat/payments/'.$payment->reference, ['state' => 'paid'])->assertMethodNotAllowed();
    $this->deleteJson('/api/v1/integrations/uchat/payments/'.$payment->reference)->assertMethodNotAllowed();
    $this->assertModelExists($payment);
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

    $response = $this->withHeader('X-WhatsApp-Phone', $customer->phone_number)->withHeader('Idempotency-Key', (string) Str::uuid())->postJson("/api/v1/integrations/uchat/bookings/{$booking->reference}/payments");

    $response
        ->assertCreated()
        ->assertJsonPath('data.amount_baisa', 12000)
        ->assertJsonPath('data.checkout_url', 'https://uatcheckout.thawani.om/pay/checkout_api_full?key=test_publishable_key')
        ->assertJsonMissingPath('data.request_payload')
        ->assertJsonPath('data.booking_installment.amount_baisa', 12000)
        ->assertJsonMissingPath('data.amount');

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

test('customer can settle a zero total booking payment through the api', function () {
    apiV1ConfigureThawani();
    Http::preventStrayRequests();

    [$customer, $booking] = apiV1PayableBookingFixture(amountBaisa: 12000);

    $booking->forceFill([
        'discount_amount_baisa' => 12000,
        'total_baisa' => 0,
    ])->save();

    $response = $this->withHeader('X-WhatsApp-Phone', $customer->phone_number)->withHeader('Idempotency-Key', (string) Str::uuid())->postJson("/api/v1/integrations/uchat/bookings/{$booking->reference}/payments");

    $response
        ->assertCreated()
        ->assertJsonPath('data.amount_baisa', 0)
        ->assertJsonPath('data.provider', 'manual')
        ->assertJsonPath('data.state', 'paid')
        ->assertJsonPath('data.checkout_url', null)
        ->assertJsonPath('data.booking_installment.amount_baisa', 0)
        ->assertJsonPath('data.booking_installment.state', 'paid');

    $schedule = $booking->refresh()->paymentSchedule()->with('installments.payments')->firstOrFail();
    $installment = $schedule->installments->first();
    $payment = $installment?->payments->first();

    expect($schedule->total_baisa)->toBe(0)
        ->and($installment)->not->toBeNull()
        ->and($installment->amount_baisa)->toBe(0)
        ->and($payment)->not->toBeNull()
        ->and($payment->provider)->toBe(PaymentProvider::Manual)
        ->and($payment->state)->toBe(PaymentState::Paid)
        ->and($payment->checkout_url)->toBeNull();

    Http::assertNothingSent();
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

    $response = $this->withHeader('X-WhatsApp-Phone', $customer->phone_number)->withHeader('Idempotency-Key', (string) Str::uuid())->postJson("/api/v1/integrations/uchat/bookings/{$booking->reference}/payments", [
        'payment_plan_id' => $paymentPlan->id,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.amount_baisa', 6000)
        ->assertJsonPath('data.checkout_url', 'https://uatcheckout.thawani.om/pay/checkout_api_plan?key=test_publishable_key')
        ->assertJsonMissingPath('data.request_payload')
        ->assertJsonPath('data.booking_installment.name', 'Deposit')
        ->assertJsonPath('data.booking_installment.amount_baisa', 6000);

    $schedule = $booking->refresh()->paymentSchedule()->with('installments.payments')->firstOrFail();

    expect($schedule->event_payment_plan_id)->toBe($paymentPlan->id)
        ->and($schedule->installments)->toHaveCount(2)
        ->and($schedule->installments[0]->amount_baisa)->toBe(6000)
        ->and($schedule->installments[1]->amount_baisa)->toBe(6000)
        ->and($schedule->installments[0]->payments)->toHaveCount(1);
});

test('customer with incomplete profile cannot mutate payments through the api', function () {
    $customer = Customer::factory()->incompleteProfile()->create();
    $payment = Payment::factory()->create();
    $this->withHeader('X-WhatsApp-Phone', $customer->phone_number)->postJson('/api/v1/integrations/uchat/payments', ['state' => 'paid'])->assertMethodNotAllowed();
    $this->patchJson('/api/v1/integrations/uchat/payments/'.$payment->reference, ['amount_baisa' => 3000])->assertMethodNotAllowed();
    $this->deleteJson('/api/v1/integrations/uchat/payments/'.$payment->reference)->assertMethodNotAllowed();
    $this->assertModelExists($payment);
});

test('customer family members can be managed through the api', function () {
    $customer = Customer::factory()->create();

    $createResponse = $this->withHeader('X-WhatsApp-Phone', $customer->phone_number)->withHeader('Idempotency-Key', (string) Str::uuid())->postJson('/api/v1/integrations/uchat/family-members', [
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

    $this->withHeader('X-WhatsApp-Phone', $customer->phone_number)->withHeader('Idempotency-Key', (string) Str::uuid())->getJson('/api/v1/integrations/uchat/family-members')
        ->assertOk()
        ->assertJsonPath('data.0.id', $familyMember->id);

    $this->withHeader('X-WhatsApp-Phone', $customer->phone_number)->withHeader('Idempotency-Key', (string) Str::uuid())->getJson("/api/v1/integrations/uchat/family-members/{$familyMember->id}")
        ->assertOk()
        ->assertJsonPath('data.birth_date', '2014-01-01');

    $this->withHeader('X-WhatsApp-Phone', $customer->phone_number)->withHeader('Idempotency-Key', (string) Str::uuid())->patchJson("/api/v1/integrations/uchat/family-members/{$familyMember->id}", [
        'grade' => '7',
        'medical_notes' => 'Peanut allergy',
        'relationship_to_customer' => 'Brother',
    ])
        ->assertOk()
        ->assertJsonPath('data.grade', '7')
        ->assertJsonPath('data.medical_notes', 'Peanut allergy')
        ->assertJsonPath('data.relationship_to_customer', 'Brother');

    $this->withHeader('X-WhatsApp-Phone', $customer->phone_number)->withHeader('Idempotency-Key', (string) Str::uuid())->deleteJson("/api/v1/integrations/uchat/family-members/{$familyMember->id}")
        ->assertNoContent();

    $this->assertModelMissing($familyMember);
});

test('customer with incomplete profile cannot mutate family members through the api', function () {
    $customer = Customer::factory()->incompleteProfile()->create();
    $familyMember = FamilyMember::factory()->for($customer)->create([
        'grade' => '6',
    ]);

    $this->withHeader('X-WhatsApp-Phone', $customer->phone_number)->withHeader('Idempotency-Key', (string) Str::uuid())->postJson('/api/v1/integrations/uchat/family-members', [
        'name' => 'Salim Said',
        'birth_date' => '2014-01-01',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('profile');

    $this->withHeader('X-WhatsApp-Phone', $customer->phone_number)->withHeader('Idempotency-Key', (string) Str::uuid())->patchJson("/api/v1/integrations/uchat/family-members/{$familyMember->id}", [
        'grade' => '7',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('profile');

    $this->withHeader('X-WhatsApp-Phone', $customer->phone_number)->withHeader('Idempotency-Key', (string) Str::uuid())->deleteJson("/api/v1/integrations/uchat/family-members/{$familyMember->id}")
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
