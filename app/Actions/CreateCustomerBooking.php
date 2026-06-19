<?php

namespace App\Actions;

use App\Enums\EventStatus;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Event;
use App\Models\FamilyMember;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateCustomerBooking
{
    public function __construct(private CalculateBookingPrice $calculateBookingPrice) {}

    /**
     * @param  array{event_id: int, family_member_ids: array<int, int>}  $data
     */
    public function execute(Customer $customer, array $data): Booking
    {
        return DB::transaction(function () use ($customer, $data): Booking {
            $event = Event::query()
                ->whereKey($data['event_id'])
                ->where('status', EventStatus::Published)
                ->lockForUpdate()
                ->firstOrFail();

            $familyMemberIds = array_values(array_unique($data['family_member_ids']));
            $familyMembers = $this->familyMembers($customer, $familyMemberIds);

            $this->validateFamilyMembers($event, $familyMembers, count($familyMemberIds));

            $priceSnapshot = $this->calculateBookingPrice->execute($event, $familyMembers->count());
            $booking = Booking::create([
                'customer_id' => $customer->id,
                'event_id' => $event->id,
                ...$priceSnapshot->toBookingAttributes(),
            ]);

            foreach ($familyMembers as $familyMember) {
                $booking->familyMembers()->create([
                    'family_member_id' => $familyMember->id,
                ]);
            }

            return $booking->load(['event', 'familyMembers.familyMember', 'paymentSchedule.installments']);
        });
    }

    /**
     * @param  array<int, int>  $familyMemberIds
     * @return Collection<int, FamilyMember>
     */
    private function familyMembers(Customer $customer, array $familyMemberIds): Collection
    {
        return FamilyMember::query()
            ->whereBelongsTo($customer)
            ->whereIn('id', $familyMemberIds)
            ->get();
    }

    /**
     * @param  Collection<int, FamilyMember>  $familyMembers
     */
    private function validateFamilyMembers(Event $event, Collection $familyMembers, int $expectedFamilyMemberCount): void
    {
        if ($familyMembers->count() !== $expectedFamilyMemberCount) {
            throw ValidationException::withMessages([
                'family_member_ids' => __('ui.messages.invalid_family_member_selection'),
            ]);
        }

        foreach ($familyMembers as $familyMember) {
            $age = $familyMember->ageAt($event->starts_at ?? now());

            if ($age < $event->minimum_age || $age > $event->maximum_age) {
                throw ValidationException::withMessages([
                    'family_member_ids' => __('ui.messages.all_family_members_age_range'),
                ]);
            }
        }

        if ($familyMembers->count() > $event->remainingSeats()) {
            throw ValidationException::withMessages([
                'family_member_ids' => __('ui.messages.not_enough_seats'),
            ]);
        }
    }
}
