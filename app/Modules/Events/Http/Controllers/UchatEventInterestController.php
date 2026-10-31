<?php

namespace App\Modules\Events\Http\Controllers;

use App\Enums\EventInterestSource;
use App\Enums\EventInterestStatus;
use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Integrations\Uchat\UchatRequest;
use App\Modules\Events\Actions\ExpressEventInterest;
use App\Modules\Events\Http\Requests\LookupAssistantEventInterestRequest;
use App\Modules\Events\Http\Requests\UpsertAssistantEventInterestRequest;
use App\Modules\Events\Http\Resources\UchatEventInterestResource as EventInterestResource;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventInterest;
use App\Modules\Identity\Models\Customer;
use Illuminate\Http\JsonResponse;

class UchatEventInterestController extends Controller
{
    public function show(LookupAssistantEventInterestRequest $request): EventInterestResource
    {
        [$customer, $event] = $this->resolveCustomerAndEvent($request);
        $interest = EventInterest::query()->whereBelongsTo($customer)->whereBelongsTo($event)->firstOrFail();

        return EventInterestResource::make($interest->load(['customer', 'event']));
    }

    public function update(UpsertAssistantEventInterestRequest $request, ExpressEventInterest $express): JsonResponse
    {
        [$customer, $event] = $this->resolveCustomerAndEvent($request);
        $interest = $express->execute($customer, $event, $request->validated(), EventInterestSource::Assistant);

        return EventInterestResource::make($interest)->response()->setStatusCode($interest->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(LookupAssistantEventInterestRequest $request): EventInterestResource
    {
        [$customer, $event] = $this->resolveCustomerAndEvent($request);
        $interest = EventInterest::query()->whereBelongsTo($customer)->whereBelongsTo($event)->firstOrFail();
        $interest->update(['status' => EventInterestStatus::Withdrawn, 'withdrawn_at' => now()]);

        return EventInterestResource::make($interest->load(['customer', 'event']));
    }

    /** @return array{Customer, Event} */
    private function resolveCustomerAndEvent(UchatRequest $request): array
    {
        $customer = $request->customer();
        $slug = $request->input('event_slug', $request->query('event_slug'));

        return [
            $customer,
            Event::query()
                ->where('slug', $slug)
                ->where('status', EventStatus::Published)
                ->firstOrFail(),
        ];
    }
}
