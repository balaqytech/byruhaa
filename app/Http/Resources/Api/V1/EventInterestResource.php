<?php

namespace App\Http\Resources\Api\V1;

use App\Modules\Events\Models\EventInterest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventInterestResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        if (! $this->resource instanceof EventInterest) {
            return [];
        }

        $interest = $this->resource;

        return [
            'id' => $interest->id,
            'status' => $interest->status->value,
            'source' => $interest->source->value,
            'preferred_contact_channel' => $interest->preferred_contact_channel,
            'source_reference' => $interest->source_reference,
            'contact_consent_at' => $interest->contact_consent_at?->toJSON(),
            'last_expressed_at' => $interest->last_expressed_at->toJSON(),
            'event' => ['id' => $interest->event_id, 'name' => $interest->event->name, 'slug' => $interest->event->slug],
            'created_at' => $interest->created_at?->toJSON(),
            'updated_at' => $interest->updated_at?->toJSON(),
        ];
    }
}
