<?php

namespace App\Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivateMinorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:64'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
