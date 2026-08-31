<?php

namespace App\Modules\Store\Http\Requests;

use App\Modules\Identity\Contracts\CustomerIdentityResolver;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

abstract class UchatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $phone = app(CustomerIdentityResolver::class)->normalizePhone((string) $this->header('X-WhatsApp-Phone'));
        $this->merge(['uchat_phone' => $phone]);
    }

    public function phone(): string
    {
        return (string) $this->validated('uchat_phone');
    }

    public function customerId(): ?int
    {
        return app(CustomerIdentityResolver::class)->findCustomerIdByPhone($this->phone());
    }

    public function minorProfileId(): ?int
    {
        return filled($this->input('minor_profile_id')) ? (int) $this->input('minor_profile_id') : null;
    }

    /** @return array<int, ValidationRule|array<mixed>|string> */
    protected function phoneRules(): array
    {
        return ['required', 'string', 'phone:INTERNATIONAL,OM'];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(new JsonResponse([
            'code' => 'validation_failed',
            'message' => 'The request data is invalid.',
            'errors' => $validator->errors()->toArray(),
        ], 422));
    }
}
