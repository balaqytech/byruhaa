<?php

namespace App\Http\Requests\Api\V1;

use App\Modules\Identity\Services\PhoneNumberNormalizer;
use Illuminate\Foundation\Http\FormRequest;

class LookupAssistantEventInterestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'phone_number' => ['required_without:email', 'nullable', 'string', 'phone:INTERNATIONAL,OM'],
            'email' => ['required_without:phone_number', 'nullable', 'email', 'max:255'],
            'event_slug' => ['required', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone_number' => blank($this->input('phone_number')) ? null : app(PhoneNumberNormalizer::class)->normalize($this->input('phone_number')),
            'email' => blank($this->input('email')) ? null : mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }
}
