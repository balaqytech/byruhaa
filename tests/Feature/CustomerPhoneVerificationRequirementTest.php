<?php

use App\Modules\Finance\Actions\InitiateWalletTopUp;
use App\Modules\Finance\Data\Payments\PaymentCheckoutData;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Models\MinorProfile;
use Livewire\Livewire;

test('phone verification prompts follow the configured requirement', function (bool $required): void {
    config(['byruhaa.phone_verification.required' => $required]);
    $customer = Customer::factory()->create(['phone_verified_at' => null]);
    $this->actingAs($customer, 'customer');

    $component = Livewire::test('pages::customer.settings.profile');
    if ($required) {
        $component->assertSee('إرسال رمز التحقق');
    } else {
        $component->assertDontSee('إرسال رمز التحقق')->assertDontSee('رقم الهاتف موثّق');
    }
    expect($customer->refresh()->phone_verified_at)->toBeNull();
})->with([false, true]);

test('guardian wallet permissions and top ups respect optional phone verification', function (bool $required, bool $verified): void {
    config(['byruhaa.phone_verification.required' => $required, 'byruhaa.wallets.enabled' => true]);
    $guardian = Customer::factory()->create(['phone_verified_at' => $verified ? now() : null]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian))->create(['wallet_spending_enabled' => false]);
    $this->actingAs($guardian, 'customer');
    $allowed = ! $required || $verified;
    $topUp = $this->mock(InitiateWalletTopUp::class);
    if ($allowed) {
        $topUp->shouldReceive('execute')->once()->andReturn(new PaymentCheckoutData('PAY-TEST', 'pending', 'https://payment.test/checkout', 1000, 'OMR', 'test-session', null));
    } else {
        $topUp->shouldNotReceive('execute');
    }

    $permission = $this->post(route('customer.minor-profiles.wallet-spending', $profile));
    $funding = $this->post(route('customer.minor-profiles.wallet.top-up', $profile), ['amount_omr' => '1.000']);
    if ($allowed) {
        $permission->assertSessionHasNoErrors();
        $funding->assertSessionHasNoErrors()->assertRedirect('https://payment.test/checkout');
        expect($profile->consents()->where('purpose', 'wallet_spending')->exists())->toBeTrue();
    } else {
        $permission->assertSessionHasErrors('phone');
        $funding->assertSessionHasErrors('phone');
    }
    expect($profile->refresh()->wallet_spending_enabled)->toBe($allowed)
        ->and($guardian->refresh()->hasVerifiedPhone())->toBe($verified);
})->with([[false, false], [true, false], [true, true]]);

test('new customers enter their account without a phone OTP or fabricated verification', function (): void {
    config(['byruhaa.phone_verification.required' => false]);
    $this->post(route('register.store'), [
        'name' => 'New Guardian',
        'phone_number' => '+96891234567',
        'password' => 'safe-password',
        'password_confirmation' => 'safe-password',
    ])->assertSessionHasNoErrors()->assertRedirect(route('customer.dashboard', absolute: false));

    $this->assertAuthenticated('customer');
    $customer = Customer::query()->where('phone_number', '+96891234567')->sole();
    expect($customer->phone_verified_at)->toBeNull()
        ->and($customer->phoneVerifications()->count())->toBe(0)
        ->and($customer->requiresPhoneVerification())->toBeFalse();
});
