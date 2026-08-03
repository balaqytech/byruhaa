<?php

use App\Enums\CouponType;
use App\Modules\Affiliates\Services\AffiliateAttribution;
use App\Modules\Events\Actions\CreateCustomerBooking;
use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\Coupon;
use App\Modules\Events\Models\Discount;
use App\Modules\Events\Models\Event;
use App\Modules\Events\States\Booking\Cancelled;
use App\Modules\Events\States\Booking\Rejected;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Support\Money\MoneyFactory;
use Illuminate\Http\Request;
use Livewire\Livewire;

function fakeCouponAffiliateAttribution(): void
{
    app()->instance(AffiliateAttribution::class, new class extends AffiliateAttribution
    {
        /**
         * @return array{affiliate_id: int, code: string, name: string, captured_at: string, expires_at: string}|null
         */
        public function current(?Request $request = null): ?array
        {
            return null;
        }
    });
}

test('customer checkout applies an entered fixed coupon when it beats automatic discounts', function () {
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'price_baisa' => 10000,
        'seat_capacity' => 5,
    ]);
    $familyMembers = FamilyMember::factory()->count(2)->for($customer)->create();
    $coupon = Coupon::factory()->for($event)->create([
        'code' => 'save-family',
        'name' => 'Family coupon',
        'amount_baisa' => 4000,
        'minimum_family_members' => 2,
    ]);

    Discount::factory()->for($event)->create([
        'name' => 'Smaller automatic discount',
        'amount_baisa' => 1000,
        'minimum_family_members' => 2,
    ]);

    $this->actingAs($customer, 'customer');
    fakeCouponAffiliateAttribution();

    Livewire::test('pages::customer.events.show', ['event' => $event])
        ->set('familyMemberIds', $familyMembers->pluck('id')->all())
        ->set('couponCode', ' save-family ')
        ->assertSee('Family coupon')
        ->assertSee('8.000')
        ->assertSee('12.000')
        ->call('book')
        ->assertHasNoErrors();

    $booking = Booking::query()->firstOrFail();

    expect($booking)
        ->coupon_id->toBe($coupon->id)
        ->coupon_code->toBe('SAVE-FAMILY')
        ->discount_id->toBeNull()
        ->discount_name->toBe('Family coupon')
        ->discount_amount_baisa->toBe(8000)
        ->total_baisa->toBe(12000)
        ->and($booking->discountSource())->toBe('coupon');
});

test('automatic discount wins when it is larger than the entered coupon', function () {
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'price_baisa' => 10000,
        'seat_capacity' => 5,
    ]);
    $familyMembers = FamilyMember::factory()->count(2)->for($customer)->create();
    $discount = Discount::factory()->for($event)->create([
        'name' => 'Best automatic discount',
        'amount_baisa' => 5000,
        'minimum_family_members' => 2,
    ]);

    Coupon::factory()->for($event)->create([
        'code' => 'SMALLER',
        'name' => 'Smaller coupon',
        'amount_baisa' => 1000,
        'minimum_family_members' => 2,
    ]);

    $booking = app(CreateCustomerBooking::class)->execute($customer, [
        'event_id' => $event->id,
        'family_member_ids' => $familyMembers->pluck('id')->all(),
        'coupon_code' => 'SMALLER',
    ]);

    expect($booking)
        ->discount_id->toBe($discount->id)
        ->coupon_id->toBeNull()
        ->coupon_code->toBeNull()
        ->discount_name->toBe('Best automatic discount')
        ->discount_amount_baisa->toBe(10000)
        ->total_baisa->toBe(10000)
        ->and($booking->discountSource())->toBe('discount');
});

test('automatic discounts are capped at the booking subtotal', function () {
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'price_baisa' => 5000,
        'seat_capacity' => 5,
    ]);
    $familyMembers = FamilyMember::factory()->count(2)->for($customer)->create();
    $discount = Discount::factory()->for($event)->create([
        'name' => 'Full automatic discount',
        'amount_baisa' => 5000,
        'minimum_family_members' => 2,
    ]);

    $booking = app(CreateCustomerBooking::class)->execute($customer, [
        'event_id' => $event->id,
        'family_member_ids' => $familyMembers->pluck('id')->all(),
    ]);

    expect($booking)
        ->discount_id->toBe($discount->id)
        ->coupon_id->toBeNull()
        ->discount_name->toBe('Full automatic discount')
        ->discount_amount_baisa->toBe(10000)
        ->total_baisa->toBe(0)
        ->and($booking->discountSource())->toBe('discount');
});

test('percentage coupons are capped at the booking subtotal', function () {
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'price_baisa' => 6000,
        'seat_capacity' => 5,
    ]);
    $familyMembers = FamilyMember::factory()->count(2)->for($customer)->create();
    $coupon = Coupon::factory()
        ->percentage(10000)
        ->for($event)
        ->create([
            'code' => 'FREE100',
            'name' => 'Full coupon',
            'minimum_family_members' => 1,
        ]);

    $booking = app(CreateCustomerBooking::class)->execute($customer, [
        'event_id' => $event->id,
        'family_member_ids' => $familyMembers->pluck('id')->all(),
        'coupon_code' => 'FREE100',
    ]);

    expect($booking)
        ->coupon_id->toBe($coupon->id)
        ->discount_amount_baisa->toBe(12000)
        ->total_baisa->toBe(0)
        ->and(MoneyFactory::formatMoneyAmount($booking->discount_amount))->toBe('12.000');
});

test('invalid or ineligible coupon codes block booking submission', function () {
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'price_baisa' => 10000,
        'seat_capacity' => 5,
    ]);
    $familyMember = FamilyMember::factory()->for($customer)->create();

    Coupon::factory()->for($event)->create([
        'code' => 'TWOONLY',
        'minimum_family_members' => 2,
    ]);

    $this->postJson("/api/v1/customers/{$customer->id}/bookings", [
        'event_id' => $event->id,
        'family_member_ids' => [$familyMember->id],
        'coupon_code' => 'TWOONLY',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('coupon_code');

    expect(Booking::query()->count())->toBe(0);
});

test('customer checkout shows the coupon code error for an ineligible coupon', function () {
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'price_baisa' => 10000,
        'seat_capacity' => 5,
    ]);
    $familyMember = FamilyMember::factory()->for($customer)->create();

    Coupon::factory()->for($event)->create([
        'code' => 'TWOONLY',
        'minimum_family_members' => 2,
    ]);

    $this->actingAs($customer, 'customer');
    fakeCouponAffiliateAttribution();

    Livewire::test('pages::customer.events.show', ['event' => $event])
        ->set('familyMemberIds', [$familyMember->id])
        ->set('couponCode', 'TWOONLY')
        ->call('book')
        ->assertHasErrors(['coupon_code']);

    expect(Booking::query()->count())->toBe(0);
});

test('customer booking api accepts a valid coupon code and returns coupon snapshot fields', function () {
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'price_baisa' => 10000,
        'seat_capacity' => 5,
    ]);
    $familyMembers = FamilyMember::factory()->count(2)->for($customer)->create();

    Coupon::factory()->for($event)->create([
        'code' => 'API10',
        'name' => 'API coupon',
        'type' => CouponType::FixedAmountPerMember,
        'amount_baisa' => 2500,
        'minimum_family_members' => 2,
    ]);

    $this->postJson("/api/v1/customers/{$customer->id}/bookings", [
        'event_id' => $event->id,
        'family_member_ids' => $familyMembers->pluck('id')->all(),
        'coupon_code' => 'api10',
    ])
        ->assertCreated()
        ->assertJsonPath('data.coupon_code', 'API10')
        ->assertJsonPath('data.discount_source', 'coupon')
        ->assertJsonPath('data.discount_name', 'API coupon')
        ->assertJsonPath('data.discount_amount', '5.000')
        ->assertJsonPath('data.total', '15.000');
});

test('coupon maximum uses are consumed when bookings are created', function () {
    $event = Event::factory()->create([
        'price_baisa' => 10000,
        'seat_capacity' => 5,
    ]);
    $firstCustomer = Customer::factory()->create();
    $secondCustomer = Customer::factory()->create();
    $firstFamilyMember = FamilyMember::factory()->for($firstCustomer)->create();
    $secondFamilyMember = FamilyMember::factory()->for($secondCustomer)->create();

    $coupon = Coupon::factory()->for($event)->create([
        'code' => 'ONCE',
        'amount_baisa' => 5000,
        'maximum_uses' => 1,
    ]);

    app(CreateCustomerBooking::class)->execute($firstCustomer, [
        'event_id' => $event->id,
        'family_member_ids' => [$firstFamilyMember->id],
        'coupon_code' => 'ONCE',
    ]);

    expect($coupon->activeRedemptionsCount())->toBe(1);

    $this->postJson("/api/v1/customers/{$secondCustomer->id}/bookings", [
        'event_id' => $event->id,
        'family_member_ids' => [$secondFamilyMember->id],
        'coupon_code' => 'ONCE',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('coupon_code')
        ->assertJsonPath('errors.coupon_code.0', __('ui.messages.coupon_usage_limit_reached'));

    expect(Booking::query()->count())->toBe(1);
});

test('coupon maximum uses per customer are enforced independently from global uses', function () {
    $event = Event::factory()->create([
        'price_baisa' => 10000,
        'seat_capacity' => 5,
    ]);
    $firstCustomer = Customer::factory()->create();
    $secondCustomer = Customer::factory()->create();
    $firstCustomerFamilyMembers = FamilyMember::factory()->count(2)->for($firstCustomer)->create();
    $secondCustomerFamilyMember = FamilyMember::factory()->for($secondCustomer)->create();

    $coupon = Coupon::factory()->for($event)->create([
        'code' => 'ONEPER',
        'amount_baisa' => 5000,
        'maximum_uses' => 5,
        'maximum_uses_per_customer' => 1,
    ]);

    app(CreateCustomerBooking::class)->execute($firstCustomer, [
        'event_id' => $event->id,
        'family_member_ids' => [$firstCustomerFamilyMembers[0]->id],
        'coupon_code' => 'ONEPER',
    ]);

    $this->postJson("/api/v1/customers/{$firstCustomer->id}/bookings", [
        'event_id' => $event->id,
        'family_member_ids' => [$firstCustomerFamilyMembers[1]->id],
        'coupon_code' => 'ONEPER',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('coupon_code');

    app(CreateCustomerBooking::class)->execute($secondCustomer, [
        'event_id' => $event->id,
        'family_member_ids' => [$secondCustomerFamilyMember->id],
        'coupon_code' => 'ONEPER',
    ]);

    expect($coupon->activeRedemptionsCount())->toBe(2)
        ->and($coupon->activeRedemptionsCountForCustomer($firstCustomer->id))->toBe(1)
        ->and($coupon->activeRedemptionsCountForCustomer($secondCustomer->id))->toBe(1);
});

test('rejected and cancelled bookings release coupon usage', function (string $stateClass) {
    config(['byruhaa.approval_mechanism' => 'manual']);

    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'price_baisa' => 10000,
        'seat_capacity' => 5,
    ]);
    $familyMembers = FamilyMember::factory()->count(2)->for($customer)->create();
    $coupon = Coupon::factory()->for($event)->create([
        'code' => 'RELEASE',
        'amount_baisa' => 5000,
        'maximum_uses' => 1,
    ]);

    $booking = app(CreateCustomerBooking::class)->execute($customer, [
        'event_id' => $event->id,
        'family_member_ids' => [$familyMembers[0]->id],
        'coupon_code' => 'RELEASE',
    ]);

    expect($coupon->activeRedemptionsCount())->toBe(1);

    $booking->state->transitionTo($stateClass);

    expect($coupon->activeRedemptionsCount())->toBe(0);

    app(CreateCustomerBooking::class)->execute($customer, [
        'event_id' => $event->id,
        'family_member_ids' => [$familyMembers[1]->id],
        'coupon_code' => 'RELEASE',
    ]);

    expect($coupon->activeRedemptionsCount())->toBe(1);
})->with([
    'rejected' => Rejected::class,
    'cancelled' => Cancelled::class,
]);
