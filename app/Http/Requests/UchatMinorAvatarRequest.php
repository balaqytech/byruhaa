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
            'avatar' => ['required_without_all:avatar_base64,avatar_url', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=2048,max_height=2048', 'prohibits:avatar_base64,avatar_url'],
            'avatar_base64' => ['required_without_all:avatar,avatar_url', 'string', 'max:2800000', 'prohibits:avatar,avatar_url'],
            'avatar_url' => ['required_without_all:avatar,avatar_base64', 'string', 'url', 'max:4096', 'prohibits:avatar,avatar_base64'],
        ];
    }
}
