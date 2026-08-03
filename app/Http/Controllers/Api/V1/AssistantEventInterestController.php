<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EventInterestSource;
use App\Enums\EventInterestStatus;
use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LookupAssistantEventInterestRequest;
use App\Http\Requests\Api\V1\UpsertAssistantEventInterestRequest;
use App\Http\Resources\Api\V1\EventInterestResource;
use App\Modules\Events\Actions\ExpressEventInterest;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventInterest;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Services\PhoneNumberNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssistantEventInterestController extends Controller
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
    private function resolveCustomerAndEvent(Request $request): array
    {
        $phone = app(PhoneNumberNormalizer::class)->normalize($request->input('phone_number', $request->query('phone_number')));
        $email = $request->input('email', $request->query('email'));
        $slug = $request->input('event_slug', $request->query('event_slug'));
        $customer = Customer::query()->when($phone, fn ($q) => $q->where('phone_number', $phone))->when(! $phone && $email, fn ($q) => $q->where('email', $email))->first();

        if (! $customer) {
            abort(response()->json(['code' => 'customer_account_required', 'message' => 'يلزم إنشاء حساب أولًا.', 'registration_url' => route('register')], 409));
        }

        return [
            $customer,
            Event::query()
                ->where('slug', $slug)
                ->where('status', EventStatus::Published)
                ->firstOrFail(),
        ];
    }
}
