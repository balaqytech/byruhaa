<?php

use App\Modules\Finance\Actions\InitiateWalletTopUp;
use App\Modules\Finance\Contracts\WalletService;
use App\Modules\Finance\Data\Payments\PaymentCheckoutData;
use App\Modules\Finance\Models\WalletTopUp;
use App\Modules\Finance\Models\WalletWithdrawalRequest;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Models\MinorProfile;
use App\Services\Webhooks\ByruhaaWebhookSender;
use Illuminate\Support\Str;
use Livewire\Livewire;

test('phone verification is offered on the Wallet page when a guardian wants to withdraw', function (): void {
    config(['byruhaa.phone_verification.required' => true, 'byruhaa.wallets.enabled' => true]);
    $customer = Customer::factory()->create(['phone_verified_at' => null]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create();
    $this->actingAs($customer, 'customer');

    Livewire::test('pages::customer.settings.profile')
        ->assertDontSee('إرسال رمز التحقق')
        ->assertDontSee('رقم الهاتف موثّق');

    $this->get(route('customer.minor-profiles.wallet', $profile))
        ->assertOk()
        ->assertSee('توثيق رقم هاتف وليّ الأمر مطلوب للسحب')
        ->assertSee(route('customer.phone-verification.send'))
        ->assertSee(route('customer.phone-verification.verify'));
    expect($customer->refresh()->phone_verified_at)->toBeNull();
});

test('an unverified guardian verifies by OTP before a Wallet withdrawal can reserve funds', function (): void {
    config(['byruhaa.wallets.enabled' => true]);
    $guardian = Customer::factory()->create(['phone_verified_at' => null]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian))->create();
    $wallet = app(WalletService::class)->walletForMinorProfile($profile->id);
    WalletTopUp::query()->create([
        'wallet_id' => $wallet->id,
        'operation_key' => 'verification-withdrawal-funds',
        'status' => 'credited',
        'currency' => 'OMR',
        'amount_baisa' => 5000,
        'spendable_baisa' => 5000,
        'refundable_baisa' => 0,
        'credited_at' => now(),
    ]);
    $wallet->increment('balance_baisa', 5000);
    $code = null;
    $sender = $this->mock(ByruhaaWebhookSender::class);
    $sender->shouldReceive('canSendUchatCustomerPhoneVerificationCode')->once()->andReturnTrue();
    $sender->shouldReceive('sendUchatCustomerPhoneVerificationCode')->once()
        ->andReturnUsing(function (Customer $customer, string $verificationCode) use (&$code, $guardian): void {
            expect($customer->id)->toBe($guardian->id);
            $code = $verificationCode;
        });
    $sender->shouldReceive('sendUchatWalletWithdrawal')->once();
    $this->actingAs($guardian, 'customer');
    $requestData = ['operation_key' => (string) Str::uuid(), 'amount_omr' => '2.000', 'method' => 'cash'];

    $this->post(route('customer.minor-profiles.wallet.withdrawals.store', $profile), $requestData)
        ->assertSessionHasErrors(['withdrawal' => 'يجب توثيق رقم هاتف وليّ الأمر قبل طلب السحب من المحفظة.']);
    expect(WalletWithdrawalRequest::query()->count())->toBe(0);

    $this->post(route('customer.phone-verification.send'))->assertSessionHasNoErrors()->assertSessionHas('phone_verification_sent', true);
    $this->post(route('customer.phone-verification.verify'), ['code' => '000000'])->assertSessionHasErrors('code');
    $this->post(route('customer.phone-verification.verify'), ['code' => $code])->assertSessionHasNoErrors()->assertSessionHas('phone_verified', true);
    expect($guardian->refresh()->hasVerifiedPhone())->toBeTrue();

    $this->get(route('customer.minor-profiles.wallet', $profile))
        ->assertOk()
        ->assertDontSee('توثيق رقم هاتف وليّ الأمر مطلوب للسحب');
    $this->post(route('customer.minor-profiles.wallet.withdrawals.store', $profile), $requestData)
        ->assertSessionHasNoErrors()
        ->assertSessionHas('withdrawal_status', 'pending');
    expect(WalletWithdrawalRequest::query()->count())->toBe(1)
        ->and($wallet->topUps()->firstOrFail()->reserved_withdrawal_baisa)->toBe(2000);
});

test('phone verification cannot claim delivery when the UChat channel is unavailable', function (): void {
    config([
        'byruhaa.uchat.webhook_url' => null,
        'byruhaa.uchat.webhook_bearer_token' => null,
        'byruhaa.uchat.webhook_signing_secret' => null,
    ]);
    $guardian = Customer::factory()->create(['phone_verified_at' => null]);
    $this->actingAs($guardian, 'customer');

    $this->post(route('customer.phone-verification.send'))
        ->assertSessionHasErrors(['phone' => 'خدمة إرسال رمز التحقق غير متاحة حاليًا. يرجى التواصل مع الإدارة لإكمال طلب السحب.']);
    expect($guardian->phoneVerifications()->count())->toBe(0);
});

test('unverified guardians can manage spending and top up wallets', function (): void {
    config(['byruhaa.phone_verification.required' => true, 'byruhaa.wallets.enabled' => true]);
    $guardian = Customer::factory()->create(['phone_verified_at' => null]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian))->create(['wallet_spending_enabled' => false]);
    $this->actingAs($guardian, 'customer');
    $this->mock(InitiateWalletTopUp::class)->shouldReceive('execute')->once()
        ->andReturn(new PaymentCheckoutData('PAY-TEST', 'pending', 'https://payment.test/checkout', 1000, 'OMR', 'test-session', null));

    $permission = $this->post(route('customer.minor-profiles.wallet-spending', $profile));
    $funding = $this->post(route('customer.minor-profiles.wallet.top-up', $profile), ['amount_omr' => '1.000']);
    $permission->assertSessionHasNoErrors();
    $funding->assertSessionHasNoErrors()->assertRedirect('https://payment.test/checkout');
    expect($profile->consents()->where('purpose', 'wallet_spending')->exists())->toBeTrue()
        ->and($profile->refresh()->wallet_spending_enabled)->toBeTrue()
        ->and($guardian->refresh()->hasVerifiedPhone())->toBeFalse();
});

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
        ->and($customer->phoneVerifications()->count())->toBe(0);
});
