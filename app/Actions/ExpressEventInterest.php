<?php

namespace App\Actions;

use App\Enums\EventInterestSource;
use App\Enums\EventInterestStatus;
use App\Models\Customer;
use App\Models\Event;
use App\Models\EventInterest;
use App\Services\Webhooks\ByruhaaWebhookSender;
use Illuminate\Validation\ValidationException;

final class ExpressEventInterest
{
    public function __construct(private ByruhaaWebhookSender $webhookSender) {}

    /** @param array{preferred_contact_channel?: string|null, source_reference?: string|null, contact_consent?: bool} $data */
    public function execute(Customer $customer, Event $event, array $data, EventInterestSource $source): EventInterest
    {
        if (! $event->isPublished() || ! $event->canExpressInterest()) {
            throw ValidationException::withMessages(['event' => 'هذه الفعالية لا تستقبل إبداء الاهتمام حاليًا.']);
        }

        $interest = EventInterest::query()->updateOrCreate(
            ['customer_id' => $customer->id, 'event_id' => $event->id],
            [
                'status' => EventInterestStatus::Interested,
                'source' => $source,
                'preferred_contact_channel' => $data['preferred_contact_channel'] ?? null,
                'source_reference' => $data['source_reference'] ?? null,
                'contact_consent_at' => ($data['contact_consent'] ?? false) ? now() : null,
                'last_expressed_at' => now(),
                'withdrawn_at' => null,
            ],
        )->load(['customer', 'event']);

        if ($interest->wasRecentlyCreated) {
            $this->webhookSender->sendInterestCreated($interest);
        }

        return $interest;
    }
}
