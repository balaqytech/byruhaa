<?php

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

class TrackOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:32'],
            'phone' => ['required', 'string', 'max:32'],
        ];
    }
}
