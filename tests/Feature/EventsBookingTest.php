<?php

use App\Enums\EventEnrollmentStatus;
use App\Modules\Affiliates\Services\AffiliateAttribution;
use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\BookingFamilyMember;
use App\Modules\Events\Models\Discount;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventContract;
use App\Modules\Events\Models\EventPaymentPlan;
use App\Modules\Events\Models\EventPaymentPlanInstallment;
use App\Modules\Events\Services\BookingApprovalService;
use App\Modules\Events\States\Booking\Approved;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Models\User;
use App\Support\Money\MoneyFactory;
use Brick\Money\Money;
use Illuminate\Http\Request;
use Livewire\Livewire;

function fakeAffiliateAttribution(): void
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

test('customer can submit a booking request for multiple family members', function () {
    $customer = Customer::factory()->create();
    $event = Event::factory()->create(['seat_capacity' => 5]);
    $familyMembers = FamilyMember::factory()->count(2)->for($customer)->create();

    $this->actingAs($customer, 'customer')
        ->get(route('customer.events.show', $event))
        ->assertOk()
        ->assertSee(__('ui.events.checkout_title'))
        ->assertDontSee('أبدِ اهتمامك');

    $booking = Booking::create([
        'customer_id' => $customer->id,
        'event_id' => $event->id,
    ]);

    foreach ($familyMembers as $familyMember) {
        $booking->familyMembers()->create([
            'family_member_id' => $familyMember->id,
        ]);
    }

    expect($booking->familyMembers()->count())->toBe(2);
});

test('customer event views show the per family member price', function () {
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'name' => 'Mountain Trip',
        'price_baisa' => 12500,
    ]);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.events.index'))
        ->assertOk()
        ->assertSee('Mountain Trip')
        ->assertSee('data-omr-symbol', false)
        ->assertSee('12.500');

    $this->actingAs($customer, 'customer')
        ->get(route('customer.events.show', $event))
        ->assertOk()
        ->assertSee(__('ui.events.price_per_family_member'))
        ->assertSee('data-omr-symbol', false)
        ->assertSee('12.500');
});

test('customer event view shows available discounts', function () {
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'name' => 'Mountain Trip',
        'price_baisa' => 12500,
    ]);
    $otherEvent = Event::factory()->create();

    Discount::factory()->for($event)->create([
        'name' => 'Sibling Discount',
        'amount_baisa' => 2500,
        'minimum_family_members' => 2,
        'maximum_family_members' => 2,
    ]);
    Discount::factory()->create([
        'name' => 'Global Discount',
        'amount_baisa' => 1000,
    ]);
    Discount::factory()->for($event)->create([
        'name' => 'Expired Discount',
        'amount_baisa' => 5000,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->subDay(),
    ]);
    Discount::factory()->for($otherEvent)->create([
        'name' => 'Other Event Discount',
        'amount_baisa' => 7000,
    ]);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.events.show', $event))
        ->assertOk()
        ->assertSee(__('ui.events.available_discounts'))
        ->assertSee('Sibling Discount')
        ->assertSee('2.500')
        ->assertSee(__('ui.events.exact_family_members', ['count' => 2]))
        ->assertDontSee(__('ui.events.family_member_range', ['min' => 2, 'max' => 2]))
        ->assertSee('Global Discount')
        ->assertSee('1.000')
        ->assertDontSee('Expired Discount')
        ->assertDontSee('Other Event Discount');
});

test('customer event checkout updates totals and payment plan previews after selecting members', function () {
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'price_baisa' => 10000,
        'seat_capacity' => 5,
    ]);
    $familyMembers = FamilyMember::factory()->count(2)->for($customer)->create();
    $paymentPlan = EventPaymentPlan::factory()->for($event)->create(['name' => 'Two payments']);

    Discount::factory()->for($event)->create([
        'name' => 'Sibling Discount',
        'amount_baisa' => 2000,
        'minimum_family_members' => 2,
    ]);
    EventPaymentPlanInstallment::factory()->for($paymentPlan, 'paymentPlan')->create([
        'name' => 'Deposit',
        'sequence' => 1,
        'percentage' => 50,
        'due_date' => '2026-07-01',
    ]);
    EventPaymentPlanInstallment::factory()->for($paymentPlan, 'paymentPlan')->create([
        'name' => 'Final',
        'sequence' => 2,
        'percentage' => 50,
        'due_date' => '2026-08-01',
    ]);

    $this->actingAs($customer, 'customer');

    $component = Livewire::test('pages::customer.events.show', ['event' => $event])
        ->assertSee(__('ui.events.checkout_title'))
        ->assertSee(__('ui.events.order_summary'))
        ->assertSee('0.000')
        ->set('familyMemberIds', $familyMembers->pluck('id')->all())
        ->assertSee('20.000')
        ->assertSee('4.000')
        ->assertSee('16.000')
        ->assertSee('Sibling Discount')
        ->assertSee(__('ui.payments.full_payment'))
        ->assertSee(__('ui.payments.full_payment_description'))
        ->assertSee('Two payments')
        ->assertSee('Deposit')
        ->assertSee('Final')
        ->assertSee('8.000');

    $html = $component->html();

    expect(mb_strpos($html, __('ui.payments.full_payment')))
        ->toBeLessThan(mb_strpos($html, 'Two payments'));
});

test('booking open customer event shows booking checkout instead of interest action', function () {
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'enrollment_status' => EventEnrollmentStatus::BookingOpen,
    ]);

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::customer.events.show', ['event' => $event])
        ->assertSee(__('ui.events.checkout_title'))
        ->assertDontSee('أبدِ اهتمامك');
});

test('booking submission stores a price snapshot', function () {
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'price_baisa' => 12500,
        'seat_capacity' => 5,
    ]);
    $familyMembers = FamilyMember::factory()->count(2)->for($customer)->create();

    $this->actingAs($customer, 'customer');
    fakeAffiliateAttribution();

    Livewire::test('pages::customer.events.show', ['event' => $event])
        ->set('familyMemberIds', $familyMembers->pluck('id')->all())
        ->call('book')
        ->assertHasNoErrors();

    $booking = Booking::query()->firstOrFail();

    expect($booking)
        ->unit_price_baisa->toBe(12500)
        ->currency->toBe('OMR')
        ->family_member_count->toBe(2)
        ->subtotal_baisa->toBe(25000)
        ->discount_id->toBeNull()
        ->discount_name->toBeNull()
        ->discount_amount_baisa->toBe(0)
        ->total_baisa->toBe(25000)
        ->and($booking->total)->toBeInstanceOf(Money::class)
        ->and(MoneyFactory::formatMoneyAmount($booking->total))->toBe('25.000');
});

test('customer with incomplete profile cannot submit a booking request', function () {
    $customer = Customer::factory()->incompleteProfile()->create();
    $event = Event::factory()->create([
        'price_baisa' => 12500,
        'seat_capacity' => 5,
    ]);
    $familyMembers = FamilyMember::factory()->count(2)->for($customer)->create();

    $this->actingAs($customer, 'customer');
    fakeAffiliateAttribution();

    Livewire::test('pages::customer.events.show', ['event' => $event])
        ->set('familyMemberIds', $familyMembers->pluck('id')->all())
        ->call('book')
        ->assertHasErrors(['familyMemberIds']);

    expect(Booking::query()->count())->toBe(0);
});

test('booking form reports missing family members instead of disabling submission', function () {
    $customer = Customer::factory()->create();
    $event = Event::factory()->create();

    $this->actingAs($customer, 'customer');
    fakeAffiliateAttribution();

    Livewire::test('pages::customer.events.show', ['event' => $event])
        ->call('book')
        ->assertHasErrors(['familyMemberIds']);

    expect(Booking::query()->count())->toBe(0);
});

test('booking form rejects family members outside the event age range', function () {
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'minimum_age' => 17,
        'maximum_age' => 18,
    ]);
    $familyMember = FamilyMember::factory()->for($customer)->create([
        'birth_date' => now()->subYears(16)->addMonth(),
    ]);

    $this->actingAs($customer, 'customer');
    fakeAffiliateAttribution();

    Livewire::test('pages::customer.events.show', ['event' => $event])
        ->assertSee(__('ui.events.eligible_age_range', ['min' => 17, 'max' => 18]))
        ->assertSee(__('ui.events.outside_age_range'))
        ->set('familyMemberIds', [$familyMember->id])
        ->call('book')
        ->assertHasErrors(['family_member_ids']);

    expect(Booking::query()->count())->toBe(0);
});

test('booking submission applies the largest eligible discount per family member', function () {
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'price_baisa' => 10000,
        'seat_capacity' => 5,
    ]);
    $familyMembers = FamilyMember::factory()->count(3)->for($customer)->create();

    Discount::factory()->create([
        'name' => 'Small family discount',
        'amount_baisa' => 2000,
        'minimum_family_members' => 2,
    ]);
    $bestDiscount = Discount::factory()->create([
        'name' => 'Best family discount',
        'event_id' => $event->id,
        'amount_baisa' => 8000,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'minimum_family_members' => 3,
    ]);
    Discount::factory()->create([
        'name' => 'Expired discount',
        'amount_baisa' => 20000,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->subDay(),
    ]);

    $this->actingAs($customer, 'customer');
    fakeAffiliateAttribution();

    Livewire::test('pages::customer.events.show', ['event' => $event])
        ->set('familyMemberIds', $familyMembers->pluck('id')->all())
        ->call('book')
        ->assertHasNoErrors();

    $booking = Booking::query()->firstOrFail();

    expect($booking)
        ->subtotal_baisa->toBe(30000)
        ->discount_id->toBe($bestDiscount->id)
        ->discount_name->toBe('Best family discount')
        ->discount_amount_baisa->toBe(24000)
        ->total_baisa->toBe(6000)
        ->and(MoneyFactory::formatMoneyAmount($booking->discount_amount))->toBe('24.000')
        ->and(MoneyFactory::formatMoneyAmount($booking->total))->toBe('6.000');
});

test('discount amount is capped at the booking subtotal', function () {
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'price_baisa' => 5000,
        'seat_capacity' => 5,
    ]);
    $familyMembers = FamilyMember::factory()->count(3)->for($customer)->create();

    Discount::factory()->create([
        'name' => 'Oversized discount',
        'amount_baisa' => 20000,
    ]);

    $this->actingAs($customer, 'customer');
    fakeAffiliateAttribution();

    Livewire::test('pages::customer.events.show', ['event' => $event])
        ->set('familyMemberIds', $familyMembers->pluck('id')->all())
        ->call('book')
        ->assertHasNoErrors();

    $booking = Booking::query()->firstOrFail();

    expect($booking)
        ->subtotal_baisa->toBe(15000)
        ->discount_amount_baisa->toBe(15000)
        ->total_baisa->toBe(0);
});

test('booking price snapshot does not change when event price or discount changes', function () {
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'price_baisa' => 7000,
        'seat_capacity' => 5,
    ]);
    $familyMembers = FamilyMember::factory()->count(2)->for($customer)->create();
    $discount = Discount::factory()->create([
        'name' => 'Launch discount',
        'amount_baisa' => 1000,
    ]);

    $this->actingAs($customer, 'customer');
    fakeAffiliateAttribution();

    Livewire::test('pages::customer.events.show', ['event' => $event])
        ->set('familyMemberIds', $familyMembers->pluck('id')->all())
        ->call('book')
        ->assertHasNoErrors();

    $booking = Booking::query()->firstOrFail();

    $event->update(['price_baisa' => 9000]);
    $discount->update([
        'name' => 'Updated discount',
        'amount_baisa' => 3000,
    ]);

    expect($booking->refresh())
        ->unit_price_baisa->toBe(7000)
        ->discount_name->toBe('Launch discount')
        ->discount_amount_baisa->toBe(2000)
        ->total_baisa->toBe(12000);
});

test('approval does not consume seats and creates one contract per family member', function () {
    $staff = User::factory()->create();
    $customer = Customer::factory()->create();
    $event = Event::factory()->create(['seat_capacity' => 2]);
    $familyMembers = FamilyMember::factory()->count(2)->for($customer)->create();
    $booking = Booking::factory()->for($customer)->for($event)->create();

    foreach ($familyMembers as $familyMember) {
        BookingFamilyMember::factory()->for($booking)->for($familyMember)->create();
    }

    app(BookingApprovalService::class)->approve($booking, $staff);

    expect($booking->refresh()->state)->toBeInstanceOf(Approved::class)
        ->and($event->remainingSeats())->toBe(2)
        ->and(EventContract::count())->toBe(2);
});

test('approval may exceed capacity because seats are only reserved by payment', function () {
    $staff = User::factory()->create();
    $customer = Customer::factory()->create();
    $event = Event::factory()->create(['seat_capacity' => 1]);
    $booking = Booking::factory()->for($customer)->for($event)->create();
    $familyMembers = FamilyMember::factory()->count(2)->for($customer)->create();

    foreach ($familyMembers as $familyMember) {
        BookingFamilyMember::factory()->for($booking)->for($familyMember)->create();
    }

    app(BookingApprovalService::class)->approve($booking, $staff);

    expect($booking->refresh()->state)->toBeInstanceOf(Approved::class)
        ->and($event->remainingSeats())->toBe(1)
        ->and(EventContract::count())->toBe(2);
});
