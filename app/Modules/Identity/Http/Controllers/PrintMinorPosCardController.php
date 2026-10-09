<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Enums\MinorProfileStatus;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Identity\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PrintMinorPosCardController
{
    public function __invoke(Request $request, MinorProfile $minorProfile): Response
    {
        $user = $request->user('web');
        abort_unless($user instanceof User && ($user->isPanelAdministrator() || $user->canAny(['Print:PosCards', 'Manage:PosCards'])), 403);

        $minorProfile->load('posCredential');
        $credential = $minorProfile->posCredential;
        abort_unless($minorProfile->status === MinorProfileStatus::Active
            && $credential?->card_revoked_at === null
            && $credential?->token_ciphertext !== null
            && hash_equals((string) $credential->token_hash, hash('sha256', $credential->token_ciphertext)), 404);

        $qrSvg = (new Writer(new ImageRenderer(new RendererStyle(360), new SvgImageBackEnd)))
            ->writeString($credential->token_ciphertext);

        return response()->view('pages.staff.pos-card-print', [
            'memberCode' => $minorProfile->member_code,
            'qrSvg' => $qrSvg,
        ])->header('Cache-Control', 'no-store, private');
    }
}
