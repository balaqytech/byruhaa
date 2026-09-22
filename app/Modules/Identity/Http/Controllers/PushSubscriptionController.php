<?php

namespace App\Modules\Identity\Http\Controllers;

use App\Modules\Identity\Http\Requests\DeletePushSubscriptionRequest;
use App\Modules\Identity\Http\Requests\StorePushSubscriptionRequest;
use App\Modules\Identity\Models\MinorProfile;
use Illuminate\Http\JsonResponse;

class PushSubscriptionController
{
    public function store(StorePushSubscriptionRequest $request): JsonResponse
    {
        $profile = $this->profile($request);
        $this->assertAvailable($profile);

        $profile->updatePushSubscription(
            (string) $request->validated('endpoint'),
            (string) $request->validated('keys.p256dh'),
            (string) $request->validated('keys.auth'),
            (string) $request->validated('content_encoding'),
        );

        return response()->json(['subscribed' => true]);
    }

    public function destroy(DeletePushSubscriptionRequest $request): JsonResponse
    {
        $profile = $this->profile($request);
        $profile->deletePushSubscription((string) $request->validated('endpoint'));

        return response()->json(['subscribed' => false]);
    }

    private function assertAvailable(MinorProfile $profile): void
    {
        abort_unless(config('byruhaa.minor_accounts.browser_notifications.enabled', true), 404);
        abort_unless($profile->isActive(), 403);
        abort_unless($profile->consents()->where('purpose', 'browser_notifications')->exists(), 403);
    }

    private function profile(StorePushSubscriptionRequest|DeletePushSubscriptionRequest $request): MinorProfile
    {
        $profile = $request->user('minor-profile');
        abort_unless($profile instanceof MinorProfile, 403);

        return $profile;
    }
}
