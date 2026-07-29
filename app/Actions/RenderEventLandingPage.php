<?php

namespace App\Actions;

use App\Enums\SeatAllocationState;
use App\Models\Discount;
use App\Models\Event;
use App\Support\EventLandingPageRegistry;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;

final readonly class RenderEventLandingPage
{
    public function __construct(
        private EventLandingPageRegistry $landingPages,
        private ViewFactory $views,
    ) {}

    public function handle(Event $event): View
    {
        $event->loadSum([
            'seatAllocations as unavailable_seats_count' => fn ($query) => $query->whereIn('state', [
                SeatAllocationState::Held->value,
                SeatAllocationState::Reserved->value,
            ]),
        ], 'seat_count');

        $event->load([
            'priceTiers' => fn ($query) => $query
                ->where('is_active', true)
                ->withSum([
                    'seatAllocations as unavailable_seats_count' => fn ($query) => $query->whereIn('state', [
                        SeatAllocationState::Held->value,
                        SeatAllocationState::Reserved->value,
                    ]),
                ], 'seat_count')
                ->orderBy('position'),
            'paymentPlans' => fn ($query) => $query
                ->where('is_active', true)
                ->with('installments')
                ->orderBy('name'),
        ]);

        $remainingSeats = $event->remainingSeats();

        return $this->views->make($this->landingPages->resolve($event->landing_page_key), [
            'event' => $event,
            'remainingSeats' => $remainingSeats,
            'availableDiscounts' => Discount::query()
                ->availableForEvent($event)
                ->orderByDesc('amount_baisa')
                ->orderBy('id')
                ->get(),
            'paymentPlans' => $event->paymentPlans,
            'eventUrl' => route('events.show', $event),
            'bookingUrl' => route('customer.events.show', $event),
            'title' => $event->name,
            'metaDescription' => $event->excerpt ?: 'Event details for '.$event->name,
        ]);
    }
}
