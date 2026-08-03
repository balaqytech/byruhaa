<?php

namespace App\Http\Controllers;

use App\Enums\EventInterestSource;
use App\Modules\Events\Actions\ExpressEventInterest;
use App\Modules\Events\Models\Event;
use App\Modules\Identity\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class PublicEventInterestController extends Controller
{
    public function __invoke(Event $event, ExpressEventInterest $expressEventInterest): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        abort_unless($customer instanceof Customer, 403);

        $expressEventInterest->execute(
            customer: $customer,
            event: $event,
            data: ['preferred_contact_channel' => 'whatsapp', 'contact_consent' => true],
            source: EventInterestSource::Website,
        );

        return back()->with('event_interest_recorded', $event->id);
    }
}
