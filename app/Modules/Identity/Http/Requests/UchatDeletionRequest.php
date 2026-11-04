<?php

namespace App\Modules\Identity\Http\Requests;

use App\Http\Requests\Integrations\Uchat\UchatRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class UchatDeletionRequest extends UchatRequest
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
        return [...parent::rules(), 'reason' => ['nullable', 'string', 'max:2000']];
    }
}
