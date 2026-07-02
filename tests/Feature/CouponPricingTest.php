<?php

use App\Actions\CreateCustomerBooking;
use App\Enums\CouponType;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Event;
use App\Models\FamilyMember;
use App\Services\AffiliateAttribution;
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
