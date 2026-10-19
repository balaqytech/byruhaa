<?php

namespace App\Http\Requests;

use App\Modules\Store\Http\Requests\UchatRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class UchatMinorAvatarRequest extends UchatRequest
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
        return [
            'uchat_phone' => $this->phoneRules(),
            'avatar' => ['required_without:avatar_base64', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=2048,max_height=2048', 'prohibits:avatar_base64'],
            'avatar_base64' => ['required_without:avatar', 'string', 'max:2800000', 'prohibits:avatar'],
        ];
    }
}
