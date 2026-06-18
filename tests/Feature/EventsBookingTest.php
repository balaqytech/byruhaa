<?php

use App\Models\Booking;
use App\Models\BookingFamilyMember;
use App\Models\Customer;
use App\Models\Event;
use App\Models\EventContract;
use App\Models\FamilyMember;
use App\Models\User;
use App\Services\BookingApprovalService;
use App\States\Booking\Approved;
use Illuminate\Validation\ValidationException;

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
