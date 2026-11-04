<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Models\MinorProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreMinorAvatar
{
    public function __construct(private DownloadMinorAvatarImage $downloads) {}

    public function storeFile(MinorProfile $profile, UploadedFile $file): void
    {
        $contents = file_get_contents($file->getRealPath());

        if ($contents === false) {
            throw ValidationException::withMessages(['avatar' => 'تعذّر قراءة الصورة. حاول رفعها مرة أخرى.']);
        }

        $this->storeBytes($profile, $contents, $file->getMimeType() ?: '', 'avatar');
    }

    public function storeBase64(MinorProfile $profile, string $dataUri): void
    {
        if (preg_match('/^data:image\/(jpeg|png|webp);base64,([A-Za-z0-9+\/=]+)$/D', $dataUri, $matches) !== 1) {
            throw ValidationException::withMessages(['avatar_base64' => 'أرسل الصورة بصيغة JPEG أو PNG أو WebP مشفّرة بـ Base64.']);
        }

        $contents = base64_decode($matches[2], true);

        if ($contents === false) {
            throw ValidationException::withMessages(['avatar_base64' => 'بيانات الصورة غير صالحة.']);
        }

        $this->storeBytes($profile, $contents, 'image/'.$matches[1], 'avatar_base64');
    }

    public function storeUrl(MinorProfile $profile, string $url): void
    {
        $this->storeDownloadedUrl($profile, $this->downloadUrl($url));
    }

    public function downloadUrl(string $url): string
    {
        $contents = $this->downloads->download($url);
        $this->validateImage($contents, null, 'avatar_url');

        return $contents;
    }

    public function storeDownloadedUrl(MinorProfile $profile, string $contents): void
    {
        $this->storeBytes($profile, $contents, null, 'avatar_url');
    }

    private function storeBytes(MinorProfile $profile, string $contents, ?string $declaredMime, string $errorKey): void
    {
        $extension = $this->validateImage($contents, $declaredMime, $errorKey);
        $disk = Storage::disk('local');
        $path = 'minor-avatars/'.$profile->id.'/'.Str::uuid().'.'.$extension;

        if (! $disk->put($path, $contents)) {
            throw ValidationException::withMessages([$errorKey => 'تعذّر حفظ الصورة. حاول مرة أخرى.']);
        }

        $previousPath = $profile->avatar_path;

        try {
            $profile->forceFill(['avatar_path' => $path])->save();
        } catch (\Throwable $exception) {
            $disk->delete($path);

            throw $exception;
        }

        if ($previousPath !== null) {
            $disk->delete($previousPath);
        }
    }

    private function validateImage(string $contents, ?string $declaredMime, string $errorKey): string
    {
        $image = @getimagesizefromstring($contents);
        $mime = $image['mime'] ?? null;
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        if (strlen($contents) > 2 * 1024 * 1024
            || ($declaredMime !== null && $mime !== $declaredMime)
            || ! isset($extensions[$mime])
            || ($image[0] ?? 0) < 1
            || ($image[1] ?? 0) < 1
            || ($image[0] ?? 0) > 2048
            || ($image[1] ?? 0) > 2048) {
            throw ValidationException::withMessages([$errorKey => 'الصورة يجب أن تكون JPEG أو PNG أو WebP، بحجم لا يتجاوز ٢ ميغابايت وأبعاد لا تتجاوز ٢٠٤٨ بكسل.']);
        }

        return $extensions[$mime];
    }
}
