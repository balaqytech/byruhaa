<?php

use App\Enums\PaymentState;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\Wallet;
use App\Modules\Identity\Enums\MinorProfileStatus;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Models\MinorProfile;
use App\Services\Webhooks\ByruhaaWebhookSender;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    config([
        'byruhaa.uchat.api_token' => 'account-secret',
        'byruhaa.uchat.owner_key_secret' => 'owner-secret',
        'byruhaa.uchat.webhook_url' => 'https://uchat.test/webhook',
        'byruhaa.uchat.webhook_bearer_token' => 'outbound-secret',
        'byruhaa.uchat.webhook_signing_secret' => 'signing-secret',
        'byruhaa.wallets.enabled' => true,
        'byruhaa.minor_accounts.enabled' => true,
        'byruhaa.minor_accounts.policy_version' => 'minor-account-test-v1',
        'byruhaa.minor_accounts.policy_text' => 'guardian-approved-minor-account',
        'byruhaa.minor_accounts.consent_text' => 'Guardian minor account consent text.',
        'byruhaa.minor_accounts.browser_notifications.policy_version' => 'minor-notifications-test-v1',
        'byruhaa.minor_accounts.browser_notifications.policy_text' => 'guardian-approved-minor-notifications',
        'byruhaa.minor_accounts.browser_notifications.consent_text' => 'Guardian notification consent text.',
    ]);
    $this->withHeaders(['Authorization' => 'Bearer account-secret', 'X-WhatsApp-Phone' => '+96891234567', 'Accept-Language' => 'en']);
});

test('account discovery is guardian scoped and includes inactive profiles and verification state', function (): void {
    $guardian = Customer::factory()->create(['phone_number' => '+96891234567', 'phone_verified_at' => null]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian))->create(['status' => MinorProfileStatus::Suspended]);
    MinorProfile::factory()->create();

    $this->getJson('/api/v1/integrations/uchat/store/account')->assertOk()
        ->assertJsonPath('data.phone_verified', false)->assertJsonPath('data.phone_verification_required', false)
        ->assertJsonPath('data.consent_policy_version', 'minor-account-test-v1')
        ->assertJsonPath('data.consent_policy_text', 'guardian-approved-minor-account')
        ->assertJsonPath('data.consent_policy_identifier', 'guardian-approved-minor-account')
        ->assertJsonPath('data.consent_text', 'Guardian minor account consent text.')
        ->assertJsonPath('data.notifications_policy_version', 'minor-notifications-test-v1')
        ->assertJsonPath('data.notifications_policy_identifier', 'guardian-approved-minor-notifications')
        ->assertJsonPath('data.notifications_consent_text', 'Guardian notification consent text.');
    $this->getJson('/api/v1/integrations/uchat/store/minor-profiles')->assertOk()->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.minor_profile_id', $profile->id)->assertJsonPath('data.0.status', 'suspended')
        ->assertJsonMissingPath('data.0.password');
});

test('minor onboarding reuses the family birthdate and safely retries creation before activation', function (): void {
    $guardian = Customer::factory()->create(['phone_number' => '+96891234567']);
    $family = FamilyMember::factory()->for($guardian)->create(['birth_date' => now()->subYears(12)]);
    config(['byruhaa.uchat.webhook_url' => null]);
    $this->mock(ByruhaaWebhookSender::class)->shouldNotReceive('sendUchatMinorVerificationCode');
    $this->postJson('/api/v1/integrations/uchat/store/minor-profiles', ['family_member_id' => $family->id])->assertUnprocessable();
    $this->postJson('/api/v1/integrations/uchat/store/minor-profiles', [
        'family_member_id' => $family->id,
        'consent_accepted' => true,
    ])->assertUnprocessable()->assertJsonValidationErrors('notifications_consent_accepted');
    $payload = [
        'family_member_id' => $family->id,
        'consent_accepted' => true,
        'notifications_consent_accepted' => true,
    ];
    $response = $this->postJson('/api/v1/integrations/uchat/store/minor-profiles', $payload)
        ->assertCreated()->assertJsonPath('data.status', 'pending_child_activation')->assertJsonMissingPath('code');
    $id = $response->json('data.minor_profile_id');
    $activationUrl = $response->json('activation_url');
    $this->postJson('/api/v1/integrations/uchat/store/minor-profiles', $payload)->assertOk()
        ->assertJsonPath('created', false)->assertJsonPath('data.minor_profile_id', $id)->assertJsonPath('activation_url', null);
    expect($activationUrl)->toContain('signature=');
    $profile = MinorProfile::findOrFail($id);
    $notificationConsent = $profile->consents()->where('purpose', 'browser_notifications')->firstOrFail();
    expect($profile->consents()->count())->toBe(2)
        ->and($notificationConsent->policy_version)->toBe('minor-notifications-test-v1')
        ->and($notificationConsent->policy_hash)->toBe(hash('sha256', 'guardian-approved-minor-notifications'))
        ->and(MinorProfile::findOrFail($id)->verifications()->count())->toBe(0);
    $this->get($activationUrl)->assertOk();

});

test('minor creation rejects other guardians family members and adults', function (): void {
    $guardian = Customer::factory()->create(['phone_number' => '+96891234567']);
    $other = FamilyMember::factory()->create();
    $adult = FamilyMember::factory()->for($guardian)->create(['birth_date' => now()->subYears(25)]);
    $payload = ['consent_accepted' => true, 'notifications_consent_accepted' => true];
    $this->postJson('/api/v1/integrations/uchat/store/minor-profiles', ['family_member_id' => $other->id, ...$payload])->assertUnprocessable()
        ->assertJsonPath('reasons.family_member_id.0', 'family_member_unavailable');
    $this->postJson('/api/v1/integrations/uchat/store/minor-profiles', ['family_member_id' => $adult->id, ...$payload])->assertUnprocessable()
        ->assertJsonPath('reasons.birth_date.0', 'minor_age_invalid');
    expect(MinorProfile::count())->toBe(0);
});

test('minor verification keeps failed attempts and forbids cross guardian requests', function (): void {
    $guardian = Customer::factory()->create(['phone_number' => '+96891234567']);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian))->create(['status' => MinorProfileStatus::PendingGuardianVerification]);
    $verification = $profile->verifications()->create(['code_hash' => Hash::make('123456'), 'expires_at' => now()->addMinutes(10)]);
    $this->withHeader('Accept-Language', 'ar')->postJson("/api/v1/integrations/uchat/store/minor-profiles/{$profile->id}/verification/verify", ['code' => '000000', 'consent_accepted' => true])
        ->assertUnprocessable()->assertJsonPath('reasons.code.0', 'verification_code_incorrect')->assertJsonPath('errors.code.0', 'رمز التحقق غير صحيح.');
    expect($verification->refresh()->attempts)->toBe(1);
    $other = MinorProfile::factory()->create();
    $this->postJson("/api/v1/integrations/uchat/store/minor-profiles/{$other->id}/verification/send")->assertUnprocessable();
    $this->postJson("/api/v1/integrations/uchat/store/minor-profiles/{$other->id}/verification/verify", ['code' => '123456', 'consent_accepted' => true])->assertUnprocessable();
});

test('activation links replace legacy OTP and cannot be reset by the old resend endpoint', function (): void {
    $this->withoutMiddleware(ThrottleRequests::class);
    $guardian = Customer::factory()->create(['phone_number' => '+96891234567']);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian))->create(['status' => MinorProfileStatus::PendingGuardianVerification]);
    $verification = $profile->verifications()->create(['code_hash' => Hash::make('123456'), 'expires_at' => now()->addMinutes(10)]);
    $this->mock(ByruhaaWebhookSender::class)->shouldNotReceive('sendUchatMinorVerificationCode');
    $url = "/api/v1/integrations/uchat/store/minor-profiles/{$profile->id}/activation-link";
    $this->postJson($url)->assertUnprocessable();
    $first = $this->postJson($url, ['consent_accepted' => true])->assertOk()->json('activation_url');
    expect($profile->refresh()->status)->toBe(MinorProfileStatus::PendingChildActivation)
        ->and($verification->refresh()->consumed_at)->not->toBeNull();
    $second = $this->postJson($url, ['consent_accepted' => true])->assertOk()->json('activation_url');
    $this->get($first)->assertNotFound();
    $this->get($second)->assertOk();
    $this->postJson("/api/v1/integrations/uchat/store/minor-profiles/{$profile->id}/verification/send")->assertUnprocessable();
    expect($profile->refresh()->status)->toBe(MinorProfileStatus::PendingChildActivation)
        ->and($profile->consents()->count())->toBe(1);
    $other = MinorProfile::factory()->create();
    $this->postJson("/api/v1/integrations/uchat/store/minor-profiles/{$other->id}/activation-link", ['consent_accepted' => true])->assertUnprocessable();
});

test('guardian phone OTP endpoints are removed from the UChat contract', function (): void {
    Customer::factory()->create(['phone_number' => '+96891234567', 'phone_verified_at' => null]);
    $this->mock(ByruhaaWebhookSender::class)->shouldNotReceive('sendUchatCustomerPhoneVerificationCode');

    $this->postJson('/api/v1/integrations/uchat/store/phone-verification/send')->assertNotFound();
    $this->postJson('/api/v1/integrations/uchat/store/phone-verification/verify', ['code' => '000000'])->assertNotFound();
});

test('guardian controls wallet spending and operational status independently without phone verification', function (): void {
    $guardian = Customer::factory()->create(['phone_number' => '+96891234567', 'phone_verified_at' => null]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian))->create([
        'status' => MinorProfileStatus::Active,
        'wallet_spending_enabled' => false,
    ]);

    $statusUrl = "/api/v1/integrations/uchat/store/minor-profiles/{$profile->id}/wallet/status";
    $spendingUrl = "/api/v1/integrations/uchat/store/minor-profiles/{$profile->id}/wallet-spending";
    $walletUrl = "/api/v1/integrations/uchat/store/minor-profiles/{$profile->id}/wallet";

    $this->getJson($statusUrl)->assertOk()
        ->assertJsonPath('data.wallet_spending_enabled', false)
        ->assertJsonPath('data.wallet_status', 'active')
        ->assertJsonPath('data.can_spend', false)
        ->assertJsonPath('data.can_top_up', true);

    $this->postJson("{$spendingUrl}/enable")->assertOk()
        ->assertJsonPath('data.wallet_spending_enabled', true)
        ->assertJsonPath('data.can_spend', true);
    $this->postJson("{$spendingUrl}/enable")->assertOk();
    expect($profile->consents()->where('purpose', 'wallet_spending')->count())->toBe(1);

    $this->postJson("{$walletUrl}/suspend")->assertOk()
        ->assertJsonPath('data.wallet_status', 'suspended')
        ->assertJsonPath('data.wallet_spending_enabled', true)
        ->assertJsonPath('data.can_spend', false)
        ->assertJsonPath('data.can_top_up', false);
    $this->postJson("{$walletUrl}/suspend")->assertOk();

    $this->postJson("{$walletUrl}/activate")->assertOk()
        ->assertJsonPath('data.wallet_status', 'active')
        ->assertJsonPath('data.can_spend', true)
        ->assertJsonPath('data.can_top_up', true);
    $this->postJson("{$spendingUrl}/disable")->assertOk()
        ->assertJsonPath('data.wallet_spending_enabled', false)
        ->assertJsonPath('data.can_spend', false)
        ->assertJsonPath('data.can_top_up', true);

    $profile->update(['status' => MinorProfileStatus::Suspended]);
    $this->postJson("{$spendingUrl}/enable")->assertUnprocessable()
        ->assertJsonPath('reasons.minor_profile_id.0', 'minor_account_inactive');
    $this->postJson("{$walletUrl}/activate")->assertUnprocessable()
        ->assertJsonPath('reasons.minor_profile_id.0', 'minor_account_inactive');

    $profile->update(['status' => MinorProfileStatus::Active]);
    Wallet::query()->where('minor_profile_id', $profile->id)->update(['status' => 'closed']);
    $this->postJson("{$walletUrl}/activate")->assertUnprocessable()
        ->assertJsonPath('reasons.wallet.0', 'wallet_closed');
});

test('UChat activation links cannot reactivate active or restricted profiles', function (MinorProfileStatus $status): void {
    $guardian = Customer::factory()->create(['phone_number' => '+96891234567']);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian))->create(['status' => $status]);
    $this->withHeader('Accept-Language', 'ar')
        ->postJson("/api/v1/integrations/uchat/store/minor-profiles/{$profile->id}/activation-link", ['consent_accepted' => true])
        ->assertUnprocessable()->assertJsonPath('reasons.minor_profile_id.0', 'minor_activation_unavailable');
    expect($profile->refresh()->status)->toBe($status)->and($profile->activation_token_hash)->toBeNull();
})->with([MinorProfileStatus::Active, MinorProfileStatus::Suspended, MinorProfileStatus::DeletionRequested, MinorProfileStatus::Invalidated]);

test('legacy minor OTP resend still enforces cooldown for preexisting pending verification accounts', function (): void {
    $guardian = Customer::factory()->create(['phone_number' => '+96891234567']);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian))->create(['status' => MinorProfileStatus::PendingGuardianVerification]);
    $this->mock(ByruhaaWebhookSender::class)->shouldReceive('sendUchatMinorVerificationCode')->once();
    $this->postJson("/api/v1/integrations/uchat/store/minor-profiles/{$profile->id}/verification/send")->assertAccepted();
    $this->postJson("/api/v1/integrations/uchat/store/minor-profiles/{$profile->id}/verification/send")
        ->assertUnprocessable()->assertJsonPath('reasons.code.0', 'verification_cooldown');
});

test('wallet API reports reserved money and refund deadlines and paginates history', function (): void {
    $guardian = Customer::factory()->create(['phone_number' => '+96891234567', 'phone_verified_at' => now()]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian))->create();
    $wallet = Wallet::create(['minor_profile_id' => $profile->id, 'balance_baisa' => 5000]);
    $topUp = $wallet->topUps()->create(['operation_key' => 'reserved-topup', 'status' => 'refunding', 'currency' => 'OMR', 'amount_baisa' => 5000, 'spendable_baisa' => 5000, 'refundable_baisa' => 4000, 'reserved_refund_baisa' => 1000, 'refund_deadline_at' => now()->addHour()]);
    for ($i = 1; $i <= 51; $i++) {
        $wallet->movements()->create(['operation_key' => 'movement-'.$i, 'type' => 'top_up', 'credit_baisa' => 100, 'debit_baisa' => 0, 'balance_after_baisa' => 100 * $i]);
    }
    $this->getJson("/api/v1/integrations/uchat/store/wallet?minor_profile_id={$profile->id}")->assertOk()
        ->assertJsonPath('balance_baisa', 5000)->assertJsonPath('available_balance_baisa', 4000)->assertJsonPath('reserved_balance_baisa', 1000)->assertJsonPath('eligible_refund_baisa', 3000)->assertJsonCount(50, 'movements');
    $this->getJson("/api/v1/integrations/uchat/store/wallet/movements?minor_profile_id={$profile->id}&per_page=50&page=2")->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 51)->assertJsonPath('data.0.operation_key', null);
    $topUp->update(['refund_deadline_at' => now()->subSecond()]);
    $this->getJson("/api/v1/integrations/uchat/store/wallet?minor_profile_id={$profile->id}")->assertOk()->assertJsonPath('eligible_refund_baisa', 0);
    $this->getJson("/api/v1/integrations/uchat/store/wallet/movements?minor_profile_id={$profile->id}&per_page=101")->assertUnprocessable();
});

test('top up status distinguishes paid from credited and hides other wallets', function (): void {
    $guardian = Customer::factory()->create(['phone_number' => '+96891234567', 'phone_verified_at' => now()]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian))->create();
    $wallet = Wallet::create(['minor_profile_id' => $profile->id]);
    $payment = Payment::create(['subject_type' => 'wallet_topup', 'provider' => 'thawani', 'amount_baisa' => 5000, 'currency' => 'OMR', 'state' => PaymentState::Paid]);
    $topUp = $wallet->topUps()->create(['operation_key' => 'paid-pending', 'status' => 'pending', 'amount_baisa' => 5000, 'currency' => 'OMR', 'payment_id' => $payment->id]);
    $this->getJson("/api/v1/integrations/uchat/store/wallet/top-ups/{$topUp->reference}?minor_profile_id={$profile->id}")->assertOk()->assertJsonPath('data.payment_status', 'paid')->assertJsonPath('data.status', 'pending')->assertJsonPath('data.credited_at', null)->assertJsonMissingPath('data.operation_key');
    $other = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian))->create();
    $this->getJson("/api/v1/integrations/uchat/store/wallet/top-ups/{$topUp->reference}?minor_profile_id={$other->id}")->assertNotFound();
    $guardian->forceFill(['phone_verified_at' => null])->save();
    $this->getJson("/api/v1/integrations/uchat/store/wallet/movements?minor_profile_id={$profile->id}")->assertOk();
    $this->getJson("/api/v1/integrations/uchat/store/wallet?minor_profile_id={$profile->id}")->assertOk();
    config(['byruhaa.phone_verification.required' => true]);
    $this->getJson("/api/v1/integrations/uchat/store/wallet/movements?minor_profile_id={$profile->id}")->assertOk();
});
