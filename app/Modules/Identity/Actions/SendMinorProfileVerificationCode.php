<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Models\MinorProfile;
use App\Services\Webhooks\ByruhaaWebhookSender;

class SendMinorProfileVerificationCode
{
    public function __construct(private ByruhaaWebhookSender $sender) {}

    public function execute(MinorProfile $profile, string $code): void
    {
        $this->sender->sendUchatMinorVerificationCode($profile, $code);
    }
}
