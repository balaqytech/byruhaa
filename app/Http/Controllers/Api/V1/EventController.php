<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EventResource;
use App\Models\Discount;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EventController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $events = Event::query()
            ->where('status', EventStatus::Published)
            ->orderBy('starts_at')
            ->paginate($this->perPage($request));

        return EventResource::collection($events);
    }

    public function show(Event $event): EventResource
    {
        abort_unless($event->status === EventStatus::Published, 404);

        $event->load([
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
