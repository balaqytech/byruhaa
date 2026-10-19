<?php

use App\Modules\Identity\Enums\MinorProfileStatus;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Models\MinorProfile;
use App\Services\Webhooks\ByruhaaWebhookSender;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('guardian can add and replace a private avatar and old signed links stop working', function (): void {
    Storage::fake('local');
    $guardian = Customer::factory()->create();
    $familyMember = FamilyMember::factory()->for($guardian)->create(['birth_date' => now()->subYears(12)]);
    $this->mock(ByruhaaWebhookSender::class)->shouldNotReceive('sendUchatMinorVerificationCode');

    $this->actingAs($guardian, 'customer')->post(route('customer.minor-profiles.store'), [
        'family_member_id' => $familyMember->id,
        'browser_notifications_consent' => '1',
        'avatar' => UploadedFile::fake()->image('leader.png', 160, 160),
    ])->assertRedirect(route('customer.minor-profiles.index'));

    $profile = MinorProfile::query()->sole();
    expect($profile->avatar_path)->not->toBeNull();
    Storage::disk('local')->assertExists($profile->avatar_path);
    $firstPath = $profile->avatar_path;
    $firstUrl = $profile->avatarUrl();

    $this->get($firstUrl)->assertOk()->assertHeader('Content-Type', 'image/png');
    $this->get(route('minor-profiles.avatar', $profile))->assertForbidden();
    $this->actingAs(Customer::factory()->create(), 'customer')
        ->post(route('customer.minor-profiles.avatar.update', $profile), ['avatar' => UploadedFile::fake()->image('other.png')])
        ->assertNotFound();

    $this->actingAs($guardian, 'customer')
        ->post(route('customer.minor-profiles.avatar.update', $profile), ['avatar' => UploadedFile::fake()->image('new.jpg', 180, 180)])
        ->assertRedirect();

    $profile->refresh();
    Storage::disk('local')->assertMissing($firstPath);
    Storage::disk('local')->assertExists($profile->avatar_path);
    $this->get($firstUrl)->assertNotFound();
    $this->get($profile->avatarUrl())->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    $this->get(route('customer.minor-profiles.index'))->assertOk()->assertSee('minor-profiles/'.$profile->id.'/avatar?', false);
});

test('avatar upload rejects files that are not images', function (): void {
    Storage::fake('local');
    $guardian = Customer::factory()->create();
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian))->create();

    $this->actingAs($guardian, 'customer')->post(route('customer.minor-profiles.avatar.update', $profile), [
        'avatar' => UploadedFile::fake()->createWithContent('leader.png', 'not an image'),
    ])->assertSessionHasErrors('avatar');

    expect($profile->fresh()->avatar_path)->toBeNull();
});

test('uchat accepts base64 creation and multipart avatar updates for the owning guardian', function (): void {
    Storage::fake('local');
    config([
        'byruhaa.uchat.api_token' => 'avatar-secret',
        'byruhaa.uchat.owner_key_secret' => 'owner-secret',
        'byruhaa.minor_accounts.enabled' => true,
        'byruhaa.uchat.webhook_url' => null,
    ]);
    $this->withHeaders(['Authorization' => 'Bearer avatar-secret', 'X-WhatsApp-Phone' => '+96891234567']);
    $guardian = Customer::factory()->create(['phone_number' => '+96891234567']);
    $familyMember = FamilyMember::factory()->for($guardian)->create(['birth_date' => now()->subYears(12)]);
    $image = UploadedFile::fake()->image('leader.png', 120, 120);
    $dataUri = 'data:image/png;base64,'.base64_encode(file_get_contents($image->getRealPath()));
    $endpoint = '/api/v1/integrations/uchat/store/minor-profiles';

    $created = $this->postJson($endpoint, [
        'family_member_id' => $familyMember->id,
        'consent_accepted' => true,
        'notifications_consent_accepted' => true,
        'avatar_base64' => $dataUri,
    ])->assertCreated()->assertJsonPath('data.name', $familyMember->name);

    $profile = MinorProfile::query()->sole();
    expect($created->json('data.avatar_url'))->toContain('signature=')
        ->and($created->json('data.avatar_url_expires_at'))->not->toBeNull();
    $this->get($created->json('data.avatar_url'))->assertOk();
    $this->getJson($endpoint)->assertOk()->assertJsonPath('data.0.minor_profile_id', $profile->id);

    $updateEndpoint = $endpoint.'/'.$profile->id.'/avatar';
    $this->post($updateEndpoint, ['avatar' => UploadedFile::fake()->image('replacement.webp', 90, 90)])
        ->assertOk()->assertJsonPath('data.minor_profile_id', $profile->id);
    expect($profile->fresh()->avatar_path)->toEndWith('.webp');

    $this->postJson($updateEndpoint, ['avatar_base64' => 'data:image/png;base64,bad'])
        ->assertUnprocessable()->assertJsonValidationErrors('avatar_base64');
    $this->postJson($updateEndpoint, [])->assertUnprocessable();

    $otherProfile = MinorProfile::factory()->create(['status' => MinorProfileStatus::Active]);
    $this->postJson($endpoint.'/'.$otherProfile->id.'/avatar', ['avatar_base64' => $dataUri])
        ->assertUnprocessable();
    expect($otherProfile->fresh()->avatar_path)->toBeNull();
});

test('uchat also accepts multipart avatar during minor creation', function (): void {
    Storage::fake('local');
    config([
        'byruhaa.uchat.api_token' => 'avatar-secret',
        'byruhaa.uchat.owner_key_secret' => 'owner-secret',
        'byruhaa.minor_accounts.enabled' => true,
        'byruhaa.uchat.webhook_url' => null,
    ]);
    $this->withHeaders(['Authorization' => 'Bearer avatar-secret', 'X-WhatsApp-Phone' => '+96891234567']);
    $guardian = Customer::factory()->create(['phone_number' => '+96891234567']);
    $familyMember = FamilyMember::factory()->for($guardian)->create(['birth_date' => now()->subYears(12)]);

    $this->post('/api/v1/integrations/uchat/store/minor-profiles', [
        'family_member_id' => $familyMember->id,
        'consent_accepted' => true,
        'notifications_consent_accepted' => true,
        'avatar' => UploadedFile::fake()->image('leader.jpg', 120, 120),
    ])->assertCreated()->assertJsonPath('data.name', $familyMember->name);

    expect(MinorProfile::query()->sole()->avatar_path)->toEndWith('.jpg');
});
