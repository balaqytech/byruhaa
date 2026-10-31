<?php

namespace App\Http\Requests\Integrations\Uchat;

use App\Data\UchatIdentity;
use App\Modules\Identity\Models\Customer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class UchatRequest extends FormRequest
{
    private ?Customer $resolvedCustomer = null;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['uchat_phone' => $this->identity()->phone]);
    }

    public function phone(): string
    {
        return $this->identity()->phone;
    }

    public function customerId(): ?int
    {
        return $this->identity()->customerId;
    }

    public function identity(): UchatIdentity
    {
        return $this->attributes->get(UchatIdentity::class);
    }

    public function customer(): Customer
    {
        abort_if($this->customerId() === null, response()->json(['code' => 'customer_account_required', 'message' => __('api_uchat.customer_account_required'), 'registration_url' => route('register')], 409));

        return $this->resolvedCustomer ??= Customer::query()->findOrFail($this->customerId());
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['uchat_phone' => $this->phoneRules()];
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
