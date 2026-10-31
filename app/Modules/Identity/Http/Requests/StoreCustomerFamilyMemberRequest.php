<?php

namespace App\Modules\Identity\Http\Requests;

use App\Http\Requests\Integrations\Uchat\UchatRequest;

class StoreCustomerFamilyMemberRequest extends UchatRequest
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
            'name' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date'],
            'school_name' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:255'],
            'medical_notes' => ['nullable', 'string'],
            'relationship_to_customer' => ['nullable', 'string', 'max:255'],
        ];
    }
}
