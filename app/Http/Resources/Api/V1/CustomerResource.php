<?php

namespace App\Http\Resources\Api\V1;

use App\Modules\Identity\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Customer */
class CustomerResource extends JsonResource
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
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'civil_id' => $this->civil_id,
            'address' => $this->address,
            'wilaya' => $this->wilaya,
            'area' => $this->area,
            'additional_info' => $this->additional_info,
            'profile_complete' => $this->hasCompleteProfile(),
            'missing_required_profile_fields' => $this->missingRequiredProfileFields(),
            'email_verified_at' => $this->email_verified_at?->toJSON(),
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
