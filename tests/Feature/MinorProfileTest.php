<?php

use App\Modules\Identity\Enums\MinorProfileStatus;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Identity\Notifications\MinorOrderStatusChangedNotification;
use App\Modules\Store\Actions\InitiateStorePayment;
use App\Modules\Store\Actions\ResolveCart;
use App\Modules\Store\Events\OrderStateChanged;
use App\Modules\Store\Listeners\NotifyMinorProfileOrderStatus;
use App\Modules\Store\Models\Cart;
use App\Modules\Store\Models\Order;
use App\Services\Webhooks\ByruhaaWebhookSender;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\mock;

test('guardian can create and activate a minor profile linked to an existing family member', function () {
    $customer = Customer::factory()->create();
    $familyMember = FamilyMember::factory()->for($customer)->create();
    $verificationCode = null;

    mock(ByruhaaWebhookSender::class)
        ->shouldReceive('sendUchatMinorVerificationCode')
        ->once()
        ->andReturnUsing(function (MinorProfile $profile, string $code) use (&$verificationCode): void {
            $verificationCode = $code;
        });

    $this->actingAs($customer, 'customer')
        ->post(route('customer.minor-profiles.store'), ['family_member_id' => $familyMember->id])
        ->assertRedirect(route('customer.minor-profiles.index'));

    $profile = MinorProfile::query()->firstOrFail();

    expect($profile->family_member_id)->toBe($familyMember->id)
        ->and($profile->status)->toBe(MinorProfileStatus::PendingGuardianVerification)
        ->and($verificationCode)->toMatch('/^\d{6}$/');

    $this->actingAs($customer, 'customer')
        ->post(route('customer.minor-profiles.verify', $profile), ['code' => $verificationCode])
        ->assertRedirect(route('customer.minor-profiles.index'));

    $profile->refresh();
    $activationUrl = session('activation_url');

    expect($profile->status)->toBe(MinorProfileStatus::PendingChildActivation)
        ->and($profile->consents()->where('purpose', 'store_purchase')->exists())->toBeTrue()
        ->and($activationUrl)->toBeString();

    $this->get($activationUrl)->assertOk()->assertSee($profile->member_code);
    $this->post($activationUrl, [
        'token' => basename((string) parse_url($activationUrl, PHP_URL_PATH)),
        'password' => 'strong-password',
        'password_confirmation' => 'strong-password',
    ]);

    $profile->refresh();

    expect($profile->status)->toBe(MinorProfileStatus::Active)
        ->and(Hash::check('strong-password', $profile->password))->toBeTrue();
});

test('the same family member cannot receive two minor profiles', function () {
    $customer = Customer::factory()->create();
    $familyMember = FamilyMember::factory()->for($customer)->create();
    MinorProfile::factory()->for($familyMember)->create();

    $this->actingAs($customer, 'customer')
        ->post(route('customer.minor-profiles.store'), ['family_member_id' => $familyMember->id])
        ->assertSessionHasErrors('family_member_id');

    expect(MinorProfile::query()->count())->toBe(1);
});

test('a minor profile cannot be accessed by another guardian', function () {
    $owner = Customer::factory()->create();
    $other = Customer::factory()->create();
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($owner))->create();

    $this->actingAs($other, 'customer')
        ->post(route('customer.minor-profiles.suspend', $profile))
        ->assertNotFound();

    expect($profile->refresh()->status)->toBe(MinorProfileStatus::Active);
});

test('minor cart identity cannot reuse the guardian cart', function (): void {
    $customer = Customer::factory()->create();
    $familyMember = FamilyMember::factory()->for($customer)->create();
    $profile = MinorProfile::factory()->for($familyMember)->create();
    $guardianCart = Cart::factory()->create(['customer_id' => $customer->id]);

    expect(fn (): Cart => app(ResolveCart::class)->execute($guardianCart->token, $customer->id, false, $profile->id))
        ->toThrow(ValidationException::class);

    $minorCart = app(ResolveCart::class)->execute(null, $customer->id, true, $profile->id);

    expect($minorCart->minor_profile_id)->toBe($profile->id)
        ->and($minorCart->customer_id)->toBe($customer->id);
});

test('minor order history is limited to the authenticated minor profile', function (): void {
    $customer = Customer::factory()->create();
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create();
    $otherProfile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create();
    $order = Order::factory()->create(['customer_id' => $customer->id, 'minor_profile_id' => $profile->id]);
    $otherOrder = Order::factory()->create(['customer_id' => $customer->id, 'minor_profile_id' => $otherProfile->id]);

    $this->actingAs($profile, 'minor-profile')
        ->get(route('minor.orders.index'))
        ->assertSuccessful()
        ->assertSee($order->reference)
        ->assertDontSee($otherOrder->reference);

    $this->get(route('minor.orders.show', $otherOrder->payment_token))->assertNotFound();
});

test('minor login switches away from an active guardian session', function (): void {
    $customer = Customer::factory()->create();
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create([
        'password' => 'minor-password',
    ]);

    $this->actingAs($customer, 'customer')
        ->post(route('minor.login.store'), [
            'member_code' => $profile->member_code,
            'password' => 'minor-password',
        ])
        ->assertRedirect(route('minor.dashboard'));

    expect(auth('customer')->check())->toBeFalse()
        ->and(auth('minor-profile')->id())->toBe($profile->id);
});

test('minor orders notify the minor profile when their status changes', function (): void {
    Notification::fake();
    $customer = Customer::factory()->create();
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'minor_profile_id' => $profile->id,
    ]);

    app(NotifyMinorProfileOrderStatus::class)->handle(new OrderStateChanged($order, 'pending_payment'));

    Notification::assertSentTo($profile, MinorOrderStatusChangedNotification::class);
});

test('guardian payment is required until direct payment is explicitly enabled', function (): void {
    $customer = Customer::factory()->create();
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create([
        'direct_payment_enabled' => false,
    ]);
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'minor_profile_id' => $profile->id,
    ]);

    expect(fn (): mixed => app(InitiateStorePayment::class)->execute($order, $customer->id, $profile->id))
        ->toThrow(ValidationException::class);
});
