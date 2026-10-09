<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRabbaniyeenInterestRequest extends FormRequest
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
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'guardian_name' => ['required', 'string', 'max:255'],
            'whatsapp_number' => ['required', 'regex:/^[0-9]{8,15}$/'],
            'wilaya' => ['required', 'string', 'max:255'],
            'student_grade' => ['required', Rule::in(['7', '8', '9', '10', '11', '12', 'multiple'])],
            'recitation_level' => ['nullable', Rule::in(['fluent', 'hesitant', 'not_fluent', 'unsure'])],
            'preferred_track' => ['nullable', Rule::in(['sakinah', 'nur', 'hijrah', 'choose_after_test'])],
            'note' => ['nullable', 'string', 'max:3000'],
            'contact_consent' => ['accepted'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $phone = strtr((string) $this->input('whatsapp_number', ''), [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        $this->merge([
            'whatsapp_number' => preg_replace('/[\s()+-]+/u', '', $phone),
        ]);
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'guardian_name' => 'اسم ولي الأمر',
            'whatsapp_number' => 'رقم الواتساب',
            'wilaya' => 'الولاية',
            'student_grade' => 'صف الابن',
            'contact_consent' => 'الموافقة على التواصل',
        ];
    }
}
