<?php

namespace App\Http\Controllers;

use App\Actions\ExpressEventInterest;
use App\Enums\EventInterestSource;
use App\Models\Customer;
use App\Models\Event;
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
