<?php

namespace App\Modules\Events\Http\Requests;

use App\Http\Requests\Integrations\Uchat\UchatRequest;
use App\Modules\Identity\Services\PhoneNumberNormalizer;
use Illuminate\Validation\Rule;

class UpsertAssistantEventInterestRequest extends UchatRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'phone_number' => ['prohibited'],
            'email' => ['prohibited'],
            'event_slug' => ['required', 'string', 'max:255'],
            'contact_consent' => ['required', 'accepted'],
            'preferred_contact_channel' => ['nullable', Rule::in(['whatsapp', 'phone', 'email'])],
            'source_reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $this->merge([
            'phone_number' => blank($this->input('phone_number')) ? null : app(PhoneNumberNormalizer::class)->normalize($this->input('phone_number')),
            'email' => blank($this->input('email')) ? null : mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }
}
