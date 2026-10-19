<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Models\MinorProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ShowMinorAvatarController
{
    public function __invoke(Request $request, MinorProfile $minorProfile): BinaryFileResponse
    {
        $path = $minorProfile->avatar_path;
        abort_unless($path !== null
            && hash_equals(hash('sha256', $path), (string) $request->query('version'))
            && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
