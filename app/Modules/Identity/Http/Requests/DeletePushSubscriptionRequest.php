<?php

namespace App\Modules\Identity\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use NotificationChannels\WebPush\PushSubscription;

class DeletePushSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('minor-profile') !== null;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'endpoint' => ['required', 'url:http,https', 'max:'.PushSubscription::ENDPOINT_MAX_LENGTH],
        ];
    }
}
