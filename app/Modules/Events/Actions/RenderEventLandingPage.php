<?php

namespace App\Modules\Events\Actions;

use App\Enums\SeatAllocationState;
use App\Modules\Events\Models\Discount;
use App\Modules\Events\Models\Event;
use App\Support\EventLandingPageRegistry;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;

final readonly class RenderEventLandingPage
{
    public function __construct(
        private EventLandingPageRegistry $landingPages,
        private ViewFactory $views,
        private BuildEventPriceTierOffer $buildEventPriceTierOffer,
    ) {}

    public function handle(Event $event): View
    {
        $event->loadSum([
            'seatAllocations as unavailable_seats_count' => fn ($query) => $query->whereIn('state', [
                SeatAllocationState::Held->value,
                SeatAllocationState::Reserved->value,
            ]),
        ], 'seat_count');

        if ($event->isComingSoon()) {
            return $this->views->make('pages.public.site.events.show', [
                'event' => $event,
                'remainingSeats' => $event->remainingSeats(),
                'tierOffer' => null,
                'availableDiscounts' => collect(),
                'paymentPlans' => collect(),
                'eventUrl' => route('events.show', $event),
                'bookingUrl' => route('customer.events.show', $event),
                'title' => $event->name,
                'metaDescription' => $event->excerpt ?: 'Event details for '.$event->name,
            ]);
        }

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
            'tierOffer' => $this->buildEventPriceTierOffer->handle($event),
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
