<?php

namespace App\Modules\Store\Actions;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as LaravelValidator;
use Slimani\MediaManager\Models\File;

class ValidateCategoryImage
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function execute(array $data): array
    {
        $validator = Validator::make($data, [
            'image_id' => ['nullable', 'integer', Rule::exists('media_files', 'id')],
        ]);

        $validator->after(function (LaravelValidator $validator) use ($data): void {
            if ($validator->errors()->isNotEmpty() || blank($data['image_id'] ?? null)) {
                return;
            }

            $image = File::query()->find((int) $data['image_id']);

            if (! $image
                || ! in_array($image->mime_type, ['image/jpeg', 'image/png', 'image/webp'], true)
                || $image->size === null
                || $image->size > 5 * 1024 * 1024) {
                $validator->errors()->add('image_id', __('admin.store.category_media.invalid_image'));
            }
        });

        $validator->validate();

        return $data;
    }
}
