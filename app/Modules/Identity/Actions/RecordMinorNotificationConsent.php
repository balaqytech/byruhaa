<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Models\MinorProfile;

class RecordMinorNotificationConsent
{
    public function execute(MinorProfile $profile, ?string $ipAddress = null): void
    {
        $policyVersion = (string) config('byruhaa.minor_accounts.browser_notifications.policy_version', 'minor-account-notifications-v2');
        $policyHash = hash('sha256', (string) config('byruhaa.minor_accounts.browser_notifications.policy_text', 'guardian-consent-minor-account-notifications'));

        $profile->consents()->firstOrCreate([
            'purpose' => 'browser_notifications',
            'policy_version' => $policyVersion,
            'policy_hash' => $policyHash,
        ], [
            'accepted_at' => now(),
            'accepted_ip' => $ipAddress,
        ]);
    }
}
