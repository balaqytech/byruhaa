<?php

use App\Actions\CreateCustomerBooking;
use App\Enums\EventEnrollmentStatus;
use App\Enums\EventInterestStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Event;
use App\Models\EventInterest;
use App\Models\FamilyMember;
use Livewire\Livewire;

test('event api exposes enrollment capabilities and filters by enrollment status', function () {
    Event::factory()->create(['enrollment_status' => EventEnrollmentStatus::InterestOpen]);
    Event::factory()->create(['enrollment_status' => EventEnrollmentStatus::BookingOpen]);

    $this->getJson('/api/v1/events?enrollment_status=interest_open')
        ->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.enrollment_status', 'interest_open')
        ->assertJsonPath('data.0.can_express_interest', true)
        ->assertJsonPath('data.0.can_book', false)
        ->assertJsonPath('data.0.primary_action.type', 'express_interest');
});

test('assistant can idempotently express inspect and withdraw customer interest', function () {
    $customer = Customer::factory()->create(['phone_number' => '+96891234567']);
    $event = Event::factory()->create(['enrollment_status' => EventEnrollmentStatus::InterestOpen]);
    $payload = ['phone_number' => '91234567', 'event_slug' => $event->slug, 'contact_consent' => true, 'preferred_contact_channel' => 'whatsapp'];

    $this->putJson('/api/v1/integrations/assistant/event-interests', $payload)
        ->assertCreated()->assertJsonPath('data.status', 'interested')->assertJsonPath('data.event.slug', $event->slug);
    $this->putJson('/api/v1/integrations/assistant/event-interests', $payload)->assertOk();

    expect(EventInterest::query()->count())->toBe(1);

    $query = http_build_query(['phone_number' => '91234567', 'event_slug' => $event->slug]);
    $this->getJson('/api/v1/integrations/assistant/event-interests?'.$query)
        ->assertOk()->assertJsonMissingPath('data.customer');
    $this->deleteJson('/api/v1/integrations/assistant/event-interests?'.$query)
        ->assertOk()->assertJsonPath('data.status', 'withdrawn');
});

test('assistant receives a registration link when customer does not exist', function () {
    $event = Event::factory()->create(['enrollment_status' => EventEnrollmentStatus::InterestOpen]);

    $this->putJson('/api/v1/integrations/assistant/event-interests', [
        'phone_number' => '91234567', 'event_slug' => $event->slug, 'contact_consent' => true,
    ])->assertStatus(409)->assertJsonPath('code', 'customer_account_required')->assertJsonStructure(['registration_url']);
});

test('interest is rejected when enrollment is closed and never changes seats', function () {
    $customer = Customer::factory()->create(['phone_number' => '+96891234567']);
    $event = Event::factory()->create(['enrollment_status' => EventEnrollmentStatus::BookingClosed, 'seat_capacity' => 12]);

    $this->putJson('/api/v1/integrations/assistant/event-interests', [
        'phone_number' => $customer->phone_number, 'event_slug' => $event->slug, 'contact_consent' => true,
    ])->assertUnprocessable();

    expect($event->remainingSeats())->toBe(12)->and(Booking::query()->count())->toBe(0);
});

test('starting a booking advances an existing interest', function () {
    config(['byruhaa.approval_mechanism' => 'manual']);
    $customer = Customer::factory()->create();
    $event = Event::factory()->create(['enrollment_status' => EventEnrollmentStatus::BookingOpen, 'minimum_age' => 5, 'maximum_age' => 20]);
    $member = FamilyMember::factory()->for($customer)->create(['birth_date' => now()->subYears(10)]);
    $interest = EventInterest::factory()->for($customer)->for($event)->create();

    $booking = app(CreateCustomerBooking::class)->execute($customer, ['event_id' => $event->id, 'family_member_ids' => [$member->id]]);

    expect($interest->refresh()->status)->toBe(EventInterestStatus::BookingStarted)
        ->and($interest->booking_id)->toBe($booking->id);
});

test('registered customer can express and withdraw interest from the customer interface', function () {
    $customer = Customer::factory()->create();
    $event = Event::factory()->create(['enrollment_status' => EventEnrollmentStatus::InterestOpen]);
    $this->actingAs($customer, 'customer');

    Livewire::test('pages::customer.events.show', ['event' => $event])
        ->assertSee('أبدِ اهتمامك')
        ->call('expressInterest');

    $interest = EventInterest::query()->whereBelongsTo($customer)->whereBelongsTo($event)->firstOrFail();

    Livewire::test('pages::customer.interests.index')
        ->assertSee($event->name)
        ->call('withdraw', $interest->id);

    expect($interest->refresh()->status)->toBe(EventInterestStatus::Withdrawn);
});
