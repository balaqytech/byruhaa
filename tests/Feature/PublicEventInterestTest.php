<?php

use App\Enums\EventEnrollmentStatus;
use App\Enums\EventInterestSource;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventInterest;
use App\Modules\Identity\Models\Customer;

test('guest sees a booking link on public event page when booking is open', function () {
    $event = Event::factory()->create(['enrollment_status' => EventEnrollmentStatus::BookingOpen]);

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('احجز الآن')
        ->assertSee(route('customer.events.show', $event), false)
        ->assertDontSee('أبدِ اهتمامك');
});

test('logged in customer expresses interest directly from public event page', function () {
    $customer = Customer::factory()->create();
    $event = Event::factory()->create(['enrollment_status' => EventEnrollmentStatus::BookingOpen, 'seat_capacity' => 12]);

    $this->actingAs($customer, 'customer')
        ->from(route('events.show', $event))
        ->post(route('events.interests.store', $event))
        ->assertRedirect(route('events.show', $event))
        ->assertSessionHas('event_interest_recorded', $event->id);

    $this->actingAs($customer, 'customer')->post(route('events.interests.store', $event))->assertRedirect();

    $interest = EventInterest::query()->firstOrFail();

    expect(EventInterest::query()->count())->toBe(1)
        ->and($interest->customer_id)->toBe($customer->id)
        ->and($interest->event_id)->toBe($event->id)
        ->and($interest->source)->toBe(EventInterestSource::Website)
        ->and($event->remainingSeats())->toBe(12);
});

test('guest cannot submit public event interest', function () {
    $event = Event::factory()->create(['enrollment_status' => EventEnrollmentStatus::InterestOpen]);

    $this->post(route('events.interests.store', $event))
        ->assertRedirect(route('login'));

    expect(EventInterest::query()->count())->toBe(0);
});
