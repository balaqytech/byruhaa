<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EventStatus;
use App\Enums\SeatAllocationState;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexEventsRequest;
use App\Http\Resources\Api\V1\EventResource;
use App\Modules\Events\Models\Discount;
use App\Modules\Events\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    public function index(IndexEventsRequest $request): AnonymousResourceCollection
    {
        $events = Event::query()
            ->where('status', EventStatus::Published)
            ->when(
                $request->string('enrollment_status')->isNotEmpty(),
                fn ($query) => $query->where('enrollment_status', $request->string('enrollment_status')->toString()),
            )
            ->withSum([
                'seatAllocations as unavailable_seats_count' => fn ($query) => $query->whereIn('state', [
                    SeatAllocationState::Held->value,
                    SeatAllocationState::Reserved->value,
                ]),
            ], 'seat_count')
            ->with([
                'priceTiers' => fn ($query) => $query
                    ->where('is_active', true)
                    ->withSum([
                        'seatAllocations as unavailable_seats_count' => fn ($query) => $query->whereIn('state', [
                            SeatAllocationState::Held->value,
                            SeatAllocationState::Reserved->value,
                        ]),
                    ], 'seat_count')
                    ->orderBy('position'),
            ])
            ->orderBy('starts_at')
            ->paginate($this->perPage($request));

        return EventResource::collection($events);
    }

    public function show(Event $event): EventResource
    {
        abort_unless($event->status === EventStatus::Published, 404);

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
                ->orderBy('id'),
        ]);

        $event->setRelation(
            'availableDiscounts',
            Discount::query()
                ->availableForEvent($event)
                ->orderByDesc('amount_baisa')
                ->orderBy('id')
                ->get(),
        );

        return EventResource::make($event);
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 15), 1), 100);
    }
}
