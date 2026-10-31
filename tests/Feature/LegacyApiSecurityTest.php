<?php

use App\Modules\Finance\Models\Payment;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Models\MinorProfile;

beforeEach(function (): void {
    config([
        'byruhaa.uchat.api_token' => 'legacy-api-test-token',
        'byruhaa.uchat.owner_key_secret' => 'legacy-api-test-owner-secret',
    ]);
});

test('legacy sensitive endpoints reject unauthenticated callers', function (string $method, string $suffix): void {
    $customer = Customer::factory()->create();
    $url = str_replace('{customer}', (string) $customer->id, $suffix);
    $this->json($method, '/api/v1/'.$url)->assertNotFound();
})->with([
    ['GET', 'customers'],
    ['POST', 'customers'],
    ['GET', 'customers/{customer}'],
    ['PATCH', 'customers/{customer}'],
    ['DELETE', 'customers/{customer}'],
    ['PATCH', 'customers/{customer}/profile'],
    ['GET', 'customers/{customer}/family-members'],
    ['POST', 'customers/{customer}/family-members'],
    ['GET', 'customers/{customer}/bookings'],
    ['POST', 'customers/{customer}/bookings'],
    ['GET', 'customers/{customer}/payments'],
    ['POST', 'customers/{customer}/payments'],
    ['GET', 'integrations/assistant/event-interests'],
    ['PUT', 'integrations/assistant/event-interests'],
    ['DELETE', 'integrations/assistant/event-interests'],
]);

test('legacy authentication rejects incorrect credentials and fails closed without configuration', function (): void {
    $this->withToken('incorrect-token')->getJson('/api/v1/integrations/uchat/account')->assertUnauthorized();
    config(['byruhaa.uchat.api_token' => null]);
    $this->getJson('/api/v1/integrations/uchat/account')->assertServiceUnavailable();
});

test('legacy customer endpoints throttle repeated integration requests', function (): void {
    config(['byruhaa.uchat.rate_limit' => 1]);
    Customer::factory()->create(['phone_number' => '+96891234567']);
    $this->withHeader('X-WhatsApp-Phone', '91234567')->withToken('legacy-api-test-token')->getJson('/api/v1/integrations/uchat/account')->assertOk();
    $this->getJson('/api/v1/integrations/uchat/account')->assertTooManyRequests();
});

test('integrations cannot replace customer passwords', function (): void {
    $customer = Customer::factory()->create();
    $password = $customer->password;
    $this->withToken('legacy-api-test-token')->withHeader('X-WhatsApp-Phone', $customer->phone_number)->patchJson('/api/v1/integrations/uchat/account', [
        'password' => 'changed-password',
        'password_confirmation' => 'changed-password',
    ])->assertUnprocessable()->assertJsonValidationErrors(['password', 'password_confirmation']);
    expect($customer->refresh()->password)->toBe($password);
});

test('legacy customer and family responses expose verification and minor links', function (): void {
    $customer = Customer::factory()->create(['phone_verified_at' => now()]);
    $member = FamilyMember::factory()->for($customer)->create();
    $minor = MinorProfile::factory()->for($member, 'familyMember')->create();
    $this->withToken('legacy-api-test-token')->withHeader('X-WhatsApp-Phone', $customer->phone_number)->getJson('/api/v1/integrations/uchat/account')
        ->assertOk()->assertJsonPath('data.phone_verified_at', $customer->phone_verified_at->toJSON());
    $this->withHeader('X-WhatsApp-Phone', $customer->phone_number)->getJson('/api/v1/integrations/uchat/family-members')
        ->assertOk()->assertJsonPath('data.0.minor_profile_id', $minor->id)
        ->assertJsonPath('data.0.minor_profile_status', $minor->status->value);
});

test('payments cannot be accessed through another customer route', function (string $method): void {
    $customer = Customer::factory()->create();
    $payment = Payment::factory()->create();
    $this->withToken('legacy-api-test-token')->withHeader('X-WhatsApp-Phone', $customer->phone_number)->json($method, "/api/v1/customers/{$customer->id}/payments/{$payment->reference}")
        ->assertNotFound();
})->with(['GET', 'PATCH', 'DELETE']);

test('public events remain accessible without integration credentials', function (): void {
    $this->getJson('/api/v1/events')->assertOk();
});

test('legacy deletion cannot bypass the linked minor lifecycle', function (): void {
    $customer = Customer::factory()->create();
    $member = FamilyMember::factory()->for($customer)->create();
    $minor = MinorProfile::factory()->for($member, 'familyMember')->create();
    $this->withToken('legacy-api-test-token')->withHeader('X-WhatsApp-Phone', $customer->phone_number)->deleteJson('/api/v1/integrations/uchat/account')
        ->assertMethodNotAllowed();
    $this->withHeader('X-WhatsApp-Phone', $customer->phone_number)->deleteJson("/api/v1/integrations/uchat/family-members/{$member->id}")
        ->assertConflict()->assertJsonPath('code', 'minor_account_lifecycle_required');
    $this->assertModelExists($customer);
    $this->assertModelExists($member);
    $this->assertModelExists($minor);
});

test('updating a phone through the legacy api clears existing verification', function (): void {
    $customer = Customer::factory()->create(['phone_verified_at' => now()]);
    $this->withToken('legacy-api-test-token')->withHeader('X-WhatsApp-Phone', $customer->phone_number)->patchJson('/api/v1/integrations/uchat/account', [
        'phone_number' => '+14155552671',
    ])->assertUnprocessable()->assertJsonValidationErrors('phone_number');
    expect($customer->refresh()->phone_verified_at)->not->toBeNull();
});
