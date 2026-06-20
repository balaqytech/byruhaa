<?php

use App\Models\Booking;
use App\Models\BookingFamilyMember;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Event;
use App\Models\EventContract;
use App\Models\FamilyMember;
use App\Models\User;
use App\Services\BookingApprovalService;
use App\States\Booking\Approved;
use App\Support\Money\MoneyFactory;
use Brick\Money\Money;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

test('customer can submit a booking request for multiple family members', function () {
    $customer = Customer::factory()->create();
    $event = Event::factory()->create(['seat_capacity' => 5]);
    $familyMembers = FamilyMember::factory()->count(2)->for($customer)->create();

    $this->actingAs($customer, 'customer')
        ->get(route('events.show', $event))
        ->assertOk();

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
        ->get(route('events.index'))
        ->assertOk()
        ->assertSee('Mountain Trip')
        ->assertSee('data-omr-symbol', false)
        ->assertSee('12.500');

    $this->actingAs($customer, 'customer')
        ->get(route('events.show', $event))
        ->assertOk()
        ->assertSee(__('ui.events.price_per_family_member'))
        ->assertSee('data-omr-symbol', false)
        ->assertSee('12.500');
});

test('booking submission stores a price snapshot', function () {
    $customer = Customer::factory()->create();
    $event = Event::factory()->create([
        'price_baisa' => 12500,
        'seat_capacity' => 5,
    ]);
    $familyMembers = FamilyMember::factory()->count(2)->for($customer)->create();

    $this->actingAs($customer, 'customer');

    Livewire::test('pages::events.show', ['event' => $event])
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

    Livewire::test('pages::events.show', ['event' => $event])
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

    Livewire::test('pages::events.show', ['event' => $event])
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

    Livewire::test('pages::events.show', ['event' => $event])
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

test('approval consumes seats and creates one contract per family member', function () {
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
        ->and($event->remainingSeats())->toBe(0)
        ->and(EventContract::count())->toBe(2);
});

test('approval fails when approved seats would exceed capacity', function () {
    $staff = User::factory()->create();
    $customer = Customer::factory()->create();
    $event = Event::factory()->create(['seat_capacity' => 1]);
    $booking = Booking::factory()->for($customer)->for($event)->create();
    $familyMembers = FamilyMember::factory()->count(2)->for($customer)->create();

    foreach ($familyMembers as $familyMember) {
        BookingFamilyMember::factory()->for($booking)->for($familyMember)->create();
    }

    app(BookingApprovalService::class)->approve($booking, $staff);
})->throws(ValidationException::class);
