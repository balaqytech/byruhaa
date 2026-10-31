<?php

beforeEach(function (): void {
    config(['byruhaa.uchat.webhook_bearer_token' => 'outbound-test', 'byruhaa.uchat.webhook_signing_secret' => 'signing-test']);
});

use App\Modules\Identity\Actions\PublicAvatarAddressResolver;
use App\Modules\Identity\Enums\MinorProfileStatus;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Models\MinorProfile;
use App\Services\Webhooks\ByruhaaWebhookSender;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
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
    $endpoint = '/api/v1/integrations/uchat/minor-profiles';

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
        ->assertNotFound();
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

    $this->post('/api/v1/integrations/uchat/minor-profiles', [
        'family_member_id' => $familyMember->id,
        'consent_accepted' => true,
        'notifications_consent_accepted' => true,
        'avatar' => UploadedFile::fake()->image('leader.jpg', 120, 120),
    ])->assertCreated()->assertJsonPath('data.name', $familyMember->name);

    expect(MinorProfile::query()->sole()->avatar_path)->toEndWith('.jpg');
});

test('invalid remote avatar does not create a minor account', function (): void {
    Storage::fake('local');
    config([
        'byruhaa.uchat.api_token' => 'avatar-secret',
        'byruhaa.uchat.owner_key_secret' => 'owner-secret',
        'byruhaa.minor_accounts.enabled' => true,
    ]);
    $this->withHeaders(['Authorization' => 'Bearer avatar-secret', 'X-WhatsApp-Phone' => '+96891234567']);
    $guardian = Customer::factory()->create(['phone_number' => '+96891234567']);
    $familyMember = FamilyMember::factory()->for($guardian)->create(['birth_date' => now()->subYears(12)]);
    $url = 'https://media.uchat.test/invalid.png';

    $this->mock(PublicAvatarAddressResolver::class)
        ->shouldReceive('resolve')
        ->with('media.uchat.test')
        ->once()
        ->andReturn('93.184.216.34');
    Http::preventStrayRequests();
    Http::fake([$url => Http::response('invalid image', 200, ['Content-Type' => 'image/png'])]);

    $this->postJson('/api/v1/integrations/uchat/minor-profiles', [
        'family_member_id' => $familyMember->id,
        'consent_accepted' => true,
        'notifications_consent_accepted' => true,
        'avatar_url' => $url,
    ])->assertUnprocessable()->assertJsonValidationErrors('avatar_url');

    expect(MinorProfile::query()->count())->toBe(0);
});

test('uchat can create a minor with a remote avatar URL and then replace it by URL', function (): void {
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
    $contents = file_get_contents($image->getRealPath());
    $remoteUrl = 'https://media.uchat.test/leader.png?signature=example';

    $this->mock(PublicAvatarAddressResolver::class)
        ->shouldReceive('resolve')
        ->with('media.uchat.test')
        ->twice()
        ->andReturn('93.184.216.34');
    Http::preventStrayRequests();
    Http::fake([$remoteUrl => fn () => Http::response($contents, 200, ['Content-Type' => 'image/png'])]);

    $created = $this->postJson('/api/v1/integrations/uchat/minor-profiles', [
        'family_member_id' => $familyMember->id,
        'consent_accepted' => true,
        'notifications_consent_accepted' => true,
        'avatar_url' => $remoteUrl,
    ])->assertCreated()->assertJsonPath('data.name', $familyMember->name);

    $profile = MinorProfile::query()->sole();
    $oldPath = $profile->avatar_path;
    Storage::disk('local')->assertExists($oldPath);
    $this->get($created->json('data.avatar_url'))->assertOk()->assertHeader('Content-Type', 'image/png');

    $this->postJson('/api/v1/integrations/uchat/minor-profiles/'.$profile->id.'/avatar', [
        'avatar_url' => $remoteUrl,
    ])->assertOk();

    $profile->refresh();
    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('local')->assertExists($profile->avatar_path);
    Http::assertSentCount(2);
});

test('uchat follows a public HTTPS image redirect after checking its destination', function (): void {
    Storage::fake('local');
    config([
        'byruhaa.uchat.api_token' => 'avatar-secret',
        'byruhaa.uchat.owner_key_secret' => 'owner-secret',
    ]);
    $this->withHeaders(['Authorization' => 'Bearer avatar-secret', 'X-WhatsApp-Phone' => '+96891234567']);
    $guardian = Customer::factory()->create(['phone_number' => '+96891234567']);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian))->create();
    $image = UploadedFile::fake()->image('leader.png', 120, 120);

    $this->mock(PublicAvatarAddressResolver::class)
        ->shouldReceive('resolve')
        ->with('media.uchat.test')
        ->once()
        ->andReturn('93.184.216.34')
        ->shouldReceive('resolve')
        ->with('cdn.uchat.test')
        ->once()
        ->andReturn('93.184.216.35');
    Http::preventStrayRequests();
    Http::fake([
        'https://media.uchat.test/leader' => Http::response('', 302, ['Location' => 'https://cdn.uchat.test/leader.png']),
        'https://cdn.uchat.test/leader.png' => Http::response(file_get_contents($image->getRealPath()), 200, ['Content-Type' => 'image/png']),
    ]);

    $this->postJson('/api/v1/integrations/uchat/minor-profiles/'.$profile->id.'/avatar', [
        'avatar_url' => 'https://media.uchat.test/leader',
    ])->assertOk();

    Storage::disk('local')->assertExists($profile->fresh()->avatar_path);
    Http::assertSentCount(2);
});

test('remote avatar URLs reject private hosts, redirects, oversized bodies and non-images', function (): void {
    Storage::fake('local');
    config([
        'byruhaa.uchat.api_token' => 'avatar-secret',
        'byruhaa.uchat.owner_key_secret' => 'owner-secret',
    ]);
    $this->withHeaders(['Authorization' => 'Bearer avatar-secret', 'X-WhatsApp-Phone' => '+96891234567']);
    $guardian = Customer::factory()->create(['phone_number' => '+96891234567']);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian))->create();
    $endpoint = '/api/v1/integrations/uchat/minor-profiles/'.$profile->id.'/avatar';
    Http::preventStrayRequests();
    Http::fake();

    foreach ([
        'http://media.uchat.test/leader.png',
        'https://127.0.0.1/leader.png',
        'https://localhost/leader.png',
        'https://media.uchat.test:8443/leader.png',
        'https://user:password@media.uchat.test/leader.png',
    ] as $url) {
        $this->postJson($endpoint, ['avatar_url' => $url])->assertUnprocessable();
    }

    Http::assertNothingSent();
    expect($profile->fresh()->avatar_path)->toBeNull();

    $this->mock(PublicAvatarAddressResolver::class)
        ->shouldReceive('resolve')
        ->with('media.uchat.test')
        ->times(4)
        ->andReturn('93.184.216.34');
    $remoteUrl = 'https://media.uchat.test/leader.png';
    Http::fakeSequence()
        ->push('', 302, ['Location' => 'http://127.0.0.1/private'])
        ->push('x', 200, ['Content-Length' => '2097153'])
        ->push('not an image', 200, ['Content-Type' => 'image/png'])
        ->push(str_repeat('x', 2097153), 200, ['Content-Type' => 'image/png']);

    for ($attempt = 0; $attempt < 4; $attempt++) {
        $this->postJson($endpoint, ['avatar_url' => $remoteUrl])
            ->assertUnprocessable()->assertJsonValidationErrors('avatar_url');
    }

    expect($profile->fresh()->avatar_path)->toBeNull();
});

test('remote avatar input is mutually exclusive with file and base64 fields', function (): void {
    config([
        'byruhaa.uchat.api_token' => 'avatar-secret',
        'byruhaa.uchat.owner_key_secret' => 'owner-secret',
    ]);
    $this->withHeaders(['Authorization' => 'Bearer avatar-secret', 'X-WhatsApp-Phone' => '+96891234567']);
    $guardian = Customer::factory()->create(['phone_number' => '+96891234567']);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian))->create();

    $this->postJson('/api/v1/integrations/uchat/minor-profiles/'.$profile->id.'/avatar', [
        'avatar_url' => 'https://media.uchat.test/leader.png',
        'avatar_base64' => 'data:image/png;base64,AAAA',
    ])->assertUnprocessable()->assertJsonValidationErrors('avatar_url');
});

test('remote avatar address checks reject private and reserved IP ranges', function (): void {
    $resolver = app(PublicAvatarAddressResolver::class);

    expect($resolver->isPublicIp('93.184.216.34'))->toBeTrue()
        ->and($resolver->isPublicIp('127.0.0.1'))->toBeFalse()
        ->and($resolver->isPublicIp('10.0.0.5'))->toBeFalse()
        ->and($resolver->isPublicIp('100.100.100.200'))->toBeFalse()
        ->and($resolver->isPublicIp('169.254.169.254'))->toBeFalse()
        ->and($resolver->isPublicIp('198.18.0.1'))->toBeFalse()
        ->and($resolver->isPublicIp('2001:db8::1'))->toBeFalse()
        ->and($resolver->isPublicIp('::1'))->toBeFalse()
        ->and($resolver->isPublicIp('::ffff:127.0.0.1'))->toBeFalse();
});

test('Postman collection documents the avatar URL create and update requests', function (): void {
    $collection = json_decode(file_get_contents(base_path('byruha_api_v1.postman_collection.json')), true, flags: JSON_THROW_ON_ERROR);
    $store = collect($collection['item'])->firstWhere('name', 'Minor Profiles');
    $requests = collect($store['item'])->keyBy('name');

    expect($requests->get('Open minor account with avatar URL')['request']['body']['raw'])->toContain('avatar_url')
        ->and($requests->get('Update minor avatar from URL')['request']['body']['raw'])->toContain('minor_avatar_url')
        ->and($requests->get('Update minor avatar from Base64')['request']['body']['raw'])->toContain('avatar_base64')
        ->and($requests->get('Update minor avatar by multipart upload')['request']['body']['formdata'][0]['key'])->toBe('avatar');
});
