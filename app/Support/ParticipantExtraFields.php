<?php

namespace App\Support;

use App\Modules\Events\Models\EventContract;
use Illuminate\Validation\Rule;

class ParticipantExtraFields
{
    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            'text' => __('admin.participant_extra_fields.types.text'),
            'textarea' => __('admin.participant_extra_fields.types.textarea'),
            'select' => __('admin.participant_extra_fields.types.select'),
            'radio' => __('admin.participant_extra_fields.types.radio'),
            'checkbox' => __('admin.participant_extra_fields.types.checkbox'),
            'date' => __('admin.participant_extra_fields.types.date'),
            'number' => __('admin.participant_extra_fields.types.number'),
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, type: string, required: bool, options: array<int, string>, placeholder: string|null, help_text: string|null}>
     */
    public static function normalizeFields(mixed $fields): array
    {
        if (! is_array($fields)) {
            return [];
        }

        $normalized = [];

        foreach ($fields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $key = trim((string) ($field['key'] ?? ''));

            if (! preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $key)) {
                continue;
            }

            $type = (string) ($field['type'] ?? 'text');

            if (! array_key_exists($type, self::typeOptions())) {
                $type = 'text';
            }

            $label = trim((string) ($field['label'] ?? $key));

            $normalized[] = [
                'key' => $key,
                'label' => $label !== '' ? $label : $key,
                'type' => $type,
                'required' => (bool) ($field['required'] ?? false),
                'options' => self::normalizeOptions($field['options'] ?? null),
                'placeholder' => self::nullableString($field['placeholder'] ?? null),
                'help_text' => self::nullableString($field['help_text'] ?? null),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, array<int, mixed>>
     */
    public static function validationRules(array $fields, string $prefix): array
    {
        $rules = [];

        foreach ($fields as $field) {
            $key = $field['key'];
            $attribute = "{$prefix}.{$key}";

            $fieldRules = match ($field['type']) {
                'checkbox' => $field['required'] ? ['accepted'] : ['nullable', 'boolean'],
                'date' => [$field['required'] ? 'required' : 'nullable', 'date'],
                'number' => [$field['required'] ? 'required' : 'nullable', 'numeric'],
                'select', 'radio' => array_values(array_filter([
                    $field['required'] ? 'required' : 'nullable',
                    'string',
                    $field['options'] !== [] ? Rule::in($field['options']) : null,
                ])),
                'textarea' => [$field['required'] ? 'required' : 'nullable', 'string', 'max:5000'],
                default => [$field['required'] ? 'required' : 'nullable', 'string', 'max:255'],
            };

            $rules[$attribute] = $fieldRules;
        }

        return $rules;
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<string, mixed>  $answers
     * @return array<string, mixed>
     */
    public static function answersForStorage(array $fields, array $answers): array
    {
        $normalized = [];

        foreach ($fields as $field) {
            $key = $field['key'];
            $value = $answers[$key] ?? null;

            if ($field['type'] === 'checkbox') {
                $normalized[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);

                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $normalized[$key] = trim((string) $value);
        }

        return $normalized;
    }

    /**
     * @return array<int, array{key: string, label: string, value: string}>
     */
    public static function formattedAnswers(EventContract $contract): array
    {
        $contract->loadMissing('bookingFamilyMember.booking.event');

        $fields = self::normalizeFields($contract->bookingFamilyMember->booking->event->participant_extra_fields);
        $answers = is_array($contract->participant_extra_answers) ? $contract->participant_extra_answers : [];
        $formatted = [];

        foreach ($fields as $field) {
            $key = $field['key'];

            if (! array_key_exists($key, $answers)) {
                continue;
            }

            $value = $answers[$key];

            if ($value === null || $value === '') {
                continue;
            }

            $formatted[] = [
                'key' => $key,
                'label' => $field['label'],
                'value' => self::formatAnswerValue($field, $value),
            ];
        }

        return $formatted;
    }

    private static function formatAnswerValue(array $field, mixed $value): string
    {
        if ($field['type'] === 'checkbox') {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? __('ui.labels.yes') : __('ui.labels.no');
        }

        return (string) $value;
    }

    /**
     * @return array<int, string>
     */
    private static function normalizeOptions(mixed $options): array
    {
        if (is_string($options)) {
            $options = preg_split('/\r\n|\r|\n/', $options) ?: [];
        }

        if (! is_array($options)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (mixed $option): string => trim((string) $option),
            $options,
        ), fn (string $option): bool => $option !== ''));
    }

    private static function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
