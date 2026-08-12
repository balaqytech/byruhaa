<?php

namespace App\Http\Resources\Api\V1;

use App\Modules\Identity\Models\FamilyMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FamilyMember */
class FamilyMemberResource extends JsonResource
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
            'customer_id' => $this->customer_id,
            'name' => $this->name,
            'birth_date' => $this->birth_date->toDateString(),
            'school_name' => $this->school_name,
            'grade' => $this->grade,
            'medical_notes' => $this->medical_notes,
            'relationship_to_customer' => $this->relationship_to_customer,
            'created_at' => $this->created_at?->toJSON(),
            'updated_at' => $this->updated_at?->toJSON(),
        ];
    }
}
