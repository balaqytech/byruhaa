<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type->value,
            'status' => $this->status->value,
            'excerpt' => $this->excerpt,
            'description_html' => $this->description_html,
            'contract_terms_html' => $this->contract_terms_html,
            'location' => $this->location,
            'starts_at' => $this->starts_at?->toJSON(),
            'ends_at' => $this->ends_at?->toJSON(),
            'minimum_age' => $this->minimum_age,
            'maximum_age' => $this->maximum_age,
            'seat_capacity' => $this->seat_capacity,
            'remaining_seats' => $this->remainingSeats(),
            'price_baisa' => $this->price_baisa,
            'currency' => $this->currency,
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
