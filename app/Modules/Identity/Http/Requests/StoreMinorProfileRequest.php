<?php

namespace App\Modules\Identity\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMinorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('customer') !== null;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $guardianId = $this->user('customer')?->getAuthIdentifier();

        return [
            'family_member_id' => [
                'nullable',
                'integer',
                Rule::exists('family_members', 'id')->where(fn ($query) => $query->where('customer_id', $guardianId)),
            ],
            'name' => ['required_without:family_member_id', 'string', 'max:255'],
            'birth_date' => ['required_without:family_member_id', 'date', 'before:today'],
            'school_name' => ['nullable', 'string', 'max:255'],
            'grade' => ['nullable', 'string', 'max:64'],
            'relationship_to_customer' => ['nullable', 'string', 'max:64'],
        ];
    }
}
