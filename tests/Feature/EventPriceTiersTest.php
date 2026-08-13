<?php

use App\Enums\BookingInstallmentState;
use App\Enums\EventInterestStatus;
use App\Enums\PaymentProvider;
use App\Enums\PaymentState;
use App\Enums\SeatAllocationState;
use App\Modules\Events\Actions\CalculateBookingPrice;
use App\Modules\Events\Actions\PrepareBookingSeatHold;
use App\Modules\Events\Actions\ReleaseBookingSeats;
use App\Modules\Events\Actions\ReserveBookingSeats;
use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\BookingFamilyMember;
use App\Modules\Events\Models\BookingInstallment;
use App\Modules\Events\Models\BookingPaymentSchedule;
use App\Modules\Events\Models\BookingSeatAllocation;
use App\Modules\Events\Models\Discount;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventContract;
use App\Modules\Events\Models\EventInterest;
use App\Modules\Events\Models\EventPriceTier;
use App\Modules\Events\States\Booking\Approved;
use App\Modules\Events\States\Contract\AwaitingSignature;
use App\Modules\Events\States\Contract\Signed;
use App\Modules\Events\States\Contract\Voided;
use App\Modules\Finance\Actions\InitiateInstallmentPayment;
use App\Modules\Finance\Models\Payment;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

function tierBooking(Event $event, int $seatCount = 1, array $attributes = []): Booking
{
    $customer = Customer::factory()->create();
    $booking = Booking::factory()->for($customer)->for($event)->create([
        'state' => Approved::$name,
        'unit_price_baisa' => $event->price_baisa,
        'family_member_count' => $seatCount,
        'subtotal_baisa' => $event->price_baisa * $seatCount,
        'total_baisa' => $event->price_baisa * $seatCount,
        ...$attributes,
    ]);

    FamilyMember::factory()->count($seatCount)->for($customer)->create()->each(
        fn (FamilyMember $familyMember) => BookingFamilyMember::factory()->for($booking)->for($familyMember)->create(),
    );

    return $booking;
}

function tierPaymentSchedule(Booking $booking): BookingInstallment
{
    $schedule = BookingPaymentSchedule::factory()->for($booking)->create([
        'event_payment_plan_id' => null,
        'plan_name' => 'Full payment',
        'currency' => $booking->currency,
        'subtotal_baisa' => $booking->subtotal_baisa,
        'discount_amount_baisa' => $booking->discount_amount_baisa,
        'total_baisa' => $booking->total_baisa,
    ]);

    return BookingInstallment::factory()->for($schedule, 'paymentSchedule')->create([
        'name' => 'Full payment',
        'sequence' => 1,
        'percentage' => 100,
        'gross_amount_baisa' => $booking->subtotal_baisa,
        'discount_amount_baisa' => $booking->discount_amount_baisa,
        'amount_baisa' => $booking->total_baisa,
        'state' => BookingInstallmentState::Pending,
    ]);
}

test('a family moves as one group to the first tier that can fit every seat', function () {
    $event = Event::factory()->create(['seat_capacity' => 10, 'price_baisa' => 10000]);
    $firstTier = EventPriceTier::factory()->for($event)->create([
        'name' => 'First',
        'position' => 1,
        'seat_capacity' => 2,
        'price_baisa' => 5000,
    ]);
    EventPriceTier::factory()->for($event)->create([
        'name' => 'Second',
        'position' => 2,
        'seat_capacity' => 4,
        'price_baisa' => 7000,
    ]);
    $otherBooking = tierBooking($event);

    BookingSeatAllocation::create([
        'booking_id' => $otherBooking->id,
        'event_id' => $event->id,
        'event_price_tier_id' => $firstTier->id,
        'seat_count' => 1,
        'state' => SeatAllocationState::Reserved,
        'tier_name' => $firstTier->name,
        'tier_unit_price_baisa' => $firstTier->price_baisa,
        'reserved_at' => now(),
    ]);

    $price = app(CalculateBookingPrice::class)->execute($event, 2);

    expect($price->unitPriceBaisa)->toBe(7000)
        ->and($price->subtotalBaisa)->toBe(14000);
});

test('the event base price is used after all early bird tiers are unavailable', function () {
    $event = Event::factory()->create(['seat_capacity' => 3, 'price_baisa' => 10000]);
    $tier = EventPriceTier::factory()->for($event)->create([
        'seat_capacity' => 1,
        'price_baisa' => 5000,
    ]);
    $otherBooking = tierBooking($event);
    BookingSeatAllocation::create([
        'booking_id' => $otherBooking->id,
        'event_id' => $event->id,
        'event_price_tier_id' => $tier->id,
        'seat_count' => 1,
        'state' => SeatAllocationState::Reserved,
        'reserved_at' => now(),
    ]);

    $price = app(CalculateBookingPrice::class)->execute($event, 1);

    expect($price->unitPriceBaisa)->toBe(10000);
});

test('ordinary discounts are calculated on top of the selected early bird price', function () {
    $event = Event::factory()->create(['seat_capacity' => 5, 'price_baisa' => 10000]);
    EventPriceTier::factory()->for($event)->create(['seat_capacity' => 3, 'price_baisa' => 8000]);
    Discount::factory()->for($event)->create(['amount_baisa' => 1000]);

    $price = app(CalculateBookingPrice::class)->execute($event, 2);

    expect($price->unitPriceBaisa)->toBe(8000)
        ->and($price->subtotalBaisa)->toBe(16000)
        ->and($price->discountAmountBaisa)->toBe(2000)
        ->and($price->totalBaisa)->toBe(14000);
});

test('starting payment holds seats and the first captured payment reserves them', function () {
    $event = Event::factory()->create(['seat_capacity' => 5, 'price_baisa' => 10000]);
    $tier = EventPriceTier::factory()->for($event)->create(['seat_capacity' => 2, 'price_baisa' => 5000]);
    $booking = tierBooking($event, 2, [
        'unit_price_baisa' => 5000,
        'subtotal_baisa' => 10000,
        'total_baisa' => 10000,
    ]);
    $interest = EventInterest::factory()->for($booking->customer)->for($event)->create([
        'status' => EventInterestStatus::BookingStarted,
        'booking_id' => $booking->id,
    ]);
    $installment = tierPaymentSchedule($booking);

    $allocation = app(PrepareBookingSeatHold::class)->execute($booking->id);

    expect($allocation->state)->toBe(SeatAllocationState::Held)
        ->and($allocation->event_price_tier_id)->toBe($tier->id)
        ->and($allocation->seat_count)->toBe(2)
        ->and($event->remainingSeats())->toBe(3);

    $payment = Payment::factory()->for($installment, 'bookingInstallment')->create([
        'amount_baisa' => 10000,
        'state' => PaymentState::Paid,
        'paid_at' => now(),
    ]);
    app(ReserveBookingSeats::class)->execute($payment);

    expect($allocation->refresh()->state)->toBe(SeatAllocationState::Reserved)
        ->and($allocation->payment_id)->toBe($payment->id)
        ->and($interest->refresh()->status)->toBe(EventInterestStatus::Converted)
        ->and($interest->converted_at)->not->toBeNull()
        ->and($event->remainingSeats())->toBe(3);
});

test('a zero balance manual settlement reserves the seat', function () {
    $event = Event::factory()->create(['seat_capacity' => 1, 'price_baisa' => 0]);
    $booking = tierBooking($event, 1, [
        'unit_price_baisa' => 0,
        'subtotal_baisa' => 0,
        'total_baisa' => 0,
    ]);
    $member = $booking->familyMembers()->firstOrFail();
    EventContract::factory()->for($member, 'bookingFamilyMember')->create([
        'state' => Signed::$name,
        'signed_at' => now(),
    ]);
    $installment = tierPaymentSchedule($booking);

    $payment = app(InitiateInstallmentPayment::class)->execute($installment, $booking->customer_id);

    expect($payment->provider)->toBe(PaymentProvider::Manual)
        ->and($payment->state)->toBe(PaymentState::Paid)
        ->and($booking->seatAllocation()->firstOrFail()->state)->toBe(SeatAllocationState::Reserved)
        ->and($event->remainingSeats())->toBe(0);
});

test('an expired hold is released only after its Thawani session is cancelled', function () {
    config([
        'thawani.mode' => 'test',
        'thawani.test.secret_key' => 'test_secret_key',
        'thawani.test.publishable_key' => 'test_publishable_key',
        'thawani.test.base_url' => 'https://uatcheckout.thawani.om/api/v1',
        'thawani.test.checkout_base_url' => 'https://uatcheckout.thawani.om/pay',
    ]);
    $event = Event::factory()->create(['seat_capacity' => 1, 'price_baisa' => 10000]);
    $booking = tierBooking($event);
    $installment = tierPaymentSchedule($booking);
    $payment = Payment::factory()->for($installment, 'bookingInstallment')->create([
        'amount_baisa' => 10000,
        'provider_session_id' => 'expired_checkout',
    ]);
    $allocation = BookingSeatAllocation::create([
        'booking_id' => $booking->id,
        'event_id' => $event->id,
        'payment_id' => $payment->id,
        'seat_count' => 1,
        'state' => SeatAllocationState::Held,
        'held_at' => now()->subMinutes(20),
        'hold_expires_at' => now()->subMinutes(5),
    ]);

    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/checkout/session/expired_checkout' => Http::response([
            'success' => true,
            'data' => [
                'session_id' => 'expired_checkout',
                'payment_status' => 'unpaid',
                'total_amount' => 10000,
            ],
        ]),
        'https://uatcheckout.thawani.om/api/v1/checkout/expired_checkout/cancel' => Http::response(['success' => true]),
    ]);

    $this->artisan('seats:release-expired-holds')->assertSuccessful();

    expect($payment->refresh()->state)->toBe(PaymentState::Cancelled)
        ->and($allocation->refresh()->state)->toBe(SeatAllocationState::Released)
        ->and($event->remainingSeats())->toBe(1);

    Http::assertSentCount(2);
});

test('an expired hold remains counted when Thawani cancellation fails', function () {
    config([
        'thawani.mode' => 'test',
        'thawani.test.secret_key' => 'test_secret_key',
        'thawani.test.publishable_key' => 'test_publishable_key',
        'thawani.test.base_url' => 'https://uatcheckout.thawani.om/api/v1',
        'thawani.test.checkout_base_url' => 'https://uatcheckout.thawani.om/pay',
    ]);
    $event = Event::factory()->create(['seat_capacity' => 1, 'price_baisa' => 10000]);
    $booking = tierBooking($event);
    $installment = tierPaymentSchedule($booking);
    $payment = Payment::factory()->for($installment, 'bookingInstallment')->create([
        'amount_baisa' => 10000,
        'provider_session_id' => 'uncancellable_checkout',
    ]);
    $allocation = BookingSeatAllocation::create([
        'booking_id' => $booking->id,
        'event_id' => $event->id,
        'payment_id' => $payment->id,
        'seat_count' => 1,
        'state' => SeatAllocationState::Held,
        'held_at' => now()->subMinutes(20),
        'hold_expires_at' => now()->subMinutes(5),
    ]);

    Http::fake([
        'https://uatcheckout.thawani.om/api/v1/checkout/session/uncancellable_checkout' => Http::response([
            'success' => true,
            'data' => [
                'session_id' => 'uncancellable_checkout',
                'payment_status' => 'unpaid',
                'total_amount' => 10000,
            ],
        ]),
        'https://uatcheckout.thawani.om/api/v1/checkout/uncancellable_checkout/cancel' => Http::response([
            'success' => false,
        ], 503),
    ]);

    $this->artisan('seats:release-expired-holds')->assertSuccessful();

    expect($payment->refresh()->state)->toBe(PaymentState::Pending)
        ->and($allocation->refresh()->state)->toBe(SeatAllocationState::Held)
        ->and($event->remainingSeats())->toBe(0);
});

test('a changed tier issues new contracts instead of silently changing signed terms', function () {
    $event = Event::factory()->create(['seat_capacity' => 5, 'price_baisa' => 10000]);
    $firstTier = EventPriceTier::factory()->for($event)->create([
        'position' => 1,
        'seat_capacity' => 1,
        'price_baisa' => 5000,
    ]);
    $secondTier = EventPriceTier::factory()->for($event)->create([
        'position' => 2,
        'seat_capacity' => 2,
        'price_baisa' => 7000,
    ]);
    $otherBooking = tierBooking($event);
    BookingSeatAllocation::create([
        'booking_id' => $otherBooking->id,
        'event_id' => $event->id,
        'event_price_tier_id' => $firstTier->id,
        'seat_count' => 1,
        'state' => SeatAllocationState::Reserved,
        'reserved_at' => now(),
    ]);

    $booking = tierBooking($event, 1, [
        'unit_price_baisa' => 5000,
        'subtotal_baisa' => 5000,
        'total_baisa' => 5000,
    ]);
    $member = $booking->familyMembers()->firstOrFail();
    $oldContract = EventContract::factory()->for($member, 'bookingFamilyMember')->create([
        'state' => Signed::$name,
        'signed_at' => now(),
    ]);
    tierPaymentSchedule($booking);

    expect(fn () => app(PrepareBookingSeatHold::class)->execute($booking->id))
        ->toThrow(ValidationException::class, __('ui.messages.price_changed_contracts_must_be_resigned'));

    $booking->refresh();
    $newContract = $member->refresh()->contract()->firstOrFail();

    expect($booking->unit_price_baisa)->toBe(7000)
        ->and($booking->subtotal_baisa)->toBe(7000)
        ->and($booking->seatAllocation->event_price_tier_id)->toBe($secondTier->id)
        ->and($booking->seatAllocation->state)->toBe(SeatAllocationState::Held)
        ->and($oldContract->refresh()->state)->toBeInstanceOf(Voided::class)
        ->and($oldContract->superseded_at)->not->toBeNull()
        ->and($newContract->id)->not->toBe($oldContract->id)
        ->and($newContract->state)->toBeInstanceOf(AwaitingSignature::class);
});

test('partial refunds retain seats while a fully refunded booking releases them', function () {
    $event = Event::factory()->create(['seat_capacity' => 2, 'price_baisa' => 10000]);
    $booking = tierBooking($event);
    $installment = tierPaymentSchedule($booking);
    $allocation = BookingSeatAllocation::create([
        'booking_id' => $booking->id,
        'event_id' => $event->id,
        'seat_count' => 1,
        'state' => SeatAllocationState::Reserved,
        'reserved_at' => now(),
    ]);
    $payment = Payment::factory()->for($installment, 'bookingInstallment')->create([
        'amount_baisa' => 10000,
        'state' => PaymentState::PartiallyRefunded,
        'paid_at' => now(),
    ]);

    app(ReleaseBookingSeats::class)->execute($payment);
    expect($allocation->refresh()->state)->toBe(SeatAllocationState::Reserved);

    $payment->update(['state' => PaymentState::Refunded]);
    app(ReleaseBookingSeats::class)->execute($payment);

    expect($allocation->refresh()->state)->toBe(SeatAllocationState::Released)
        ->and($event->remainingSeats())->toBe(2);
});

test('tier capacity and deletion cannot invalidate held seats', function () {
    $event = Event::factory()->create(['seat_capacity' => 3, 'price_baisa' => 10000]);
    $tier = EventPriceTier::factory()->for($event)->create(['seat_capacity' => 2, 'price_baisa' => 5000]);
    $booking = tierBooking($event);
    BookingSeatAllocation::create([
        'booking_id' => $booking->id,
        'event_id' => $event->id,
        'event_price_tier_id' => $tier->id,
        'seat_count' => 1,
        'state' => SeatAllocationState::Held,
        'held_at' => now(),
    ]);

    expect(fn () => $tier->update(['seat_capacity' => 0]))->toThrow(ValidationException::class)
        ->and(fn () => $tier->delete())->toThrow(ValidationException::class)
        ->and(fn () => EventPriceTier::factory()->for($event)->create([
            'position' => 2,
            'seat_capacity' => 2,
            'price_baisa' => 7000,
        ]))->toThrow(ValidationException::class);
});

test('event REST endpoints expose active price tiers with their remaining seats', function () {
    $event = Event::factory()->create([
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDay()->addHours(6),
        'seat_capacity' => 5,
        'price_baisa' => 10000,
    ]);
    $firstTier = EventPriceTier::factory()->for($event)->create([
        'name' => 'First tier',
        'position' => 1,
        'seat_capacity' => 1,
        'price_baisa' => 5000,
    ]);
    EventPriceTier::factory()->for($event)->create([
        'name' => 'Second tier',
        'position' => 2,
        'seat_capacity' => 2,
        'price_baisa' => 7000,
    ]);
    EventPriceTier::factory()->for($event)->create([
        'name' => 'Inactive tier',
        'position' => 3,
        'seat_capacity' => 1,
        'price_baisa' => 8000,
        'is_active' => false,
    ]);
    $booking = tierBooking($event);
    BookingSeatAllocation::create([
        'booking_id' => $booking->id,
        'event_id' => $event->id,
        'event_price_tier_id' => $firstTier->id,
        'seat_count' => 1,
        'state' => SeatAllocationState::Held,
        'held_at' => now(),
    ]);

    $response = $this->getJson("/api/v1/events/{$event->slug}")
        ->assertOk()
        ->assertJsonPath('data.price', '10.000')
        ->assertJsonPath('data.remaining_seats', 4)
        ->assertJsonCount(2, 'data.price_tiers')
        ->assertJsonPath('data.price_tiers.0.name', 'First tier')
        ->assertJsonPath('data.price_tiers.0.position', 1)
        ->assertJsonPath('data.price_tiers.0.seat_capacity', 1)
        ->assertJsonPath('data.price_tiers.0.remaining_seats', 0)
        ->assertJsonPath('data.price_tiers.0.price', '5.000')
        ->assertJsonPath('data.price_tiers.0.currency', 'OMR')
        ->assertJsonPath('data.price_tiers.1.name', 'Second tier')
        ->assertJsonPath('data.price_tiers.1.remaining_seats', 2)
        ->assertJsonMissing(['name' => 'Inactive tier']);
    $data = $response->json('data');

    expect(array_keys($data))->toBe([
        'id', 'name', 'slug', 'type', 'status', 'enrollment_status', 'enrollment_status_label',
        'can_express_interest', 'can_book', 'primary_action', 'excerpt', 'location', 'starts_at', 'ends_at',
        'minimum_age', 'maximum_age', 'seat_capacity', 'remaining_seats', 'price', 'currency',
        'price_tiers', 'available_discounts', 'payment_plans', 'created_at', 'updated_at',
    ]);

    $indexResponse = $this->getJson('/api/v1/events?per_page=100')->assertOk();
    $indexedEvent = collect($indexResponse->json('data'))->firstWhere('id', $event->id);

    expect($indexedEvent)->not->toBeNull()
        ->and($indexedEvent['price_tiers'])->toHaveCount(2)
        ->and($indexedEvent['price_tiers'][0]['remaining_seats'])->toBe(0);
});

test('existing REST payment endpoint reserves seats without changing its response contract', function () {
    $event = Event::factory()->create(['seat_capacity' => 2, 'price_baisa' => 10000]);
    EventPriceTier::factory()->for($event)->create(['seat_capacity' => 1, 'price_baisa' => 5000]);
    $booking = tierBooking($event, 1, [
        'unit_price_baisa' => 5000,
        'subtotal_baisa' => 5000,
        'total_baisa' => 5000,
    ]);
    $member = $booking->familyMembers()->firstOrFail();
    EventContract::factory()->for($member, 'bookingFamilyMember')->create([
        'state' => Signed::$name,
        'signed_at' => now(),
    ]);
    $installment = tierPaymentSchedule($booking);

    $response = $this->postJson("/api/v1/customers/{$booking->customer_id}/payments", [
        'booking_installment_id' => $installment->id,
        'amount_baisa' => 5000,
        'state' => PaymentState::Paid->value,
        'paid_at' => now()->toJSON(),
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.booking_installment_id', $installment->id)
        ->assertJsonPath('data.amount', '5.000')
        ->assertJsonPath('data.state', PaymentState::Paid->value)
        ->assertJsonMissingPath('data.seat_allocation');

    expect($booking->seatAllocation()->firstOrFail()->state)->toBe(SeatAllocationState::Reserved)
        ->and($event->remainingSeats())->toBe(1);
});
