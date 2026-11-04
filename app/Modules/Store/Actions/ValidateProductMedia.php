<?php

namespace App\Modules\Store\Actions;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as LaravelValidator;
use Slimani\MediaManager\Models\File;

class ValidateProductMedia
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function execute(array $data): array
    {
        $validator = Validator::make($data, [
            'video_id' => ['nullable', 'integer', Rule::exists('media_files', 'id')],
            'gallery_image_ids' => ['nullable', 'array', 'max:8'],
            'gallery_image_ids.*' => ['required', 'integer', 'distinct', Rule::exists('media_files', 'id')],
        ]);

        $validator->after(function (LaravelValidator $validator) use ($data): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $videoId = filled($data['video_id'] ?? null) ? (int) $data['video_id'] : null;
            $galleryIds = array_map('intval', $data['gallery_image_ids'] ?? []);
            $files = File::query()->whereIn('id', array_filter([$videoId, ...$galleryIds]))->get()->keyBy('id');

            if ($videoId !== null) {
                $video = $files->get($videoId);

                if (! $video || ! in_array($video->mime_type, ['video/mp4', 'video/webm'], true) || $video->size === null || $video->size > 50 * 1024 * 1024) {
                    $validator->errors()->add('video_id', __('admin.store.product_media.invalid_video'));
                }
            }

            foreach ($galleryIds as $index => $imageId) {
                $image = $files->get($imageId);

                if (! $image || ! str_starts_with((string) $image->mime_type, 'image/') || $image->size === null || $image->size > 10 * 1024 * 1024) {
                    $validator->errors()->add('gallery_image_ids.'.$index, __('admin.store.product_media.invalid_image'));
                }
            }
        });

        $validator->validate();

        return $data;
    }
}
