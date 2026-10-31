<?php

namespace App\Modules\Events\Http\Requests;

use App\Enums\EventEnrollmentStatus;
use App\Http\Requests\Integrations\Uchat\UchatIdentityRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class IndexUchatEventsRequest extends UchatIdentityRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [...parent::rules(), 'enrollment_status' => ['nullable', Rule::enum(EventEnrollmentStatus::class)]];
    }
}
