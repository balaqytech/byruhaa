<?php

namespace App\Modules\Identity\Http\Requests;

use App\Http\Requests\Integrations\Uchat\UchatRequest;

class UpdateCustomerFamilyMemberRequest extends UchatRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'birth_date' => ['sometimes', 'required', 'date'],
            'school_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'grade' => ['sometimes', 'nullable', 'string', 'max:255'],
            'medical_notes' => ['sometimes', 'nullable', 'string'],
            'relationship_to_customer' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
