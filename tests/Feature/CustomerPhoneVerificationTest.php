<?php

use App\Models\WebhookDelivery;
use App\Modules\Identity\Actions\SendCustomerPhoneVerificationCode;
use App\Modules\Identity\Actions\VerifyCustomerPhone;
use App\Modules\Identity\Models\Customer;
use App\Services\Webhooks\ByruhaaWebhookSender;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\mock;

beforeEach(function (): void {
    config([
        'byruhaa.wallets.enabled' => true,
        'byruhaa.wallets.phone_otp_resend_seconds' => 0,
    ]);
});

test('a guardian can verify a phone with a durable otp attempt counter', function (): void {
    $customer = Customer::factory()->create(['phone_verified_at' => null]);
    $code = null;

    mock(ByruhaaWebhookSender::class)
        ->shouldReceive('sendUchatCustomerPhoneVerificationCode')
        ->once()
        ->andReturnUsing(function (Customer $customer, string $verificationCode, mixed ...$unused) use (&$code): void {
            $code = $verificationCode;
        });

    app(SendCustomerPhoneVerificationCode::class)->execute($customer);

    expect(fn (): mixed => app(VerifyCustomerPhone::class)->execute($customer, '000000'))
        ->toThrow(ValidationException::class);

    expect($customer->phoneVerifications()->firstOrFail()->refresh()->attempts)->toBe(1);

    app(VerifyCustomerPhone::class)->execute($customer->refresh(), $code);

    expect($customer->refresh()->phone_verified_at)->not->toBeNull()
        ->and($customer->phoneVerifications()->firstOrFail()->consumed_at)->not->toBeNull();
});

test('a phone verification cannot be reused after the phone changes', function (): void {
    $customer = Customer::factory()->create(['phone_verified_at' => null]);

    mock(ByruhaaWebhookSender::class)
        ->shouldReceive('sendUchatCustomerPhoneVerificationCode')
        ->once()
        ->andReturnUsing(function (): void {});

    app(SendCustomerPhoneVerificationCode::class)->execute($customer);
    $customer->forceFill(['phone_number' => '+96891234567'])->save();

    expect(fn (): mixed => app(VerifyCustomerPhone::class)->execute($customer->refresh(), '123456'))
        ->toThrow(ValidationException::class);

    expect($customer->refresh()->phone_verified_at)->toBeNull();
});

test('changing a verified phone invalidates the verification state', function (): void {
    $customer = Customer::factory()->create(['phone_verified_at' => now()]);

    $customer->update(['phone_number' => '+96891234567']);

    expect($customer->refresh()->phone_verified_at)->toBeNull();
});

test('expired phone verification codes are rejected', function (): void {
    $customer = Customer::factory()->create(['phone_verified_at' => null]);

    mock(ByruhaaWebhookSender::class)
        ->shouldReceive('sendUchatCustomerPhoneVerificationCode')
        ->once()
        ->andReturnUsing(function (): void {});

    app(SendCustomerPhoneVerificationCode::class)->execute($customer);
    $customer->phoneVerifications()->firstOrFail()->forceFill(['expires_at' => Carbon::now()->subMinute()])->save();

    expect(fn (): mixed => app(VerifyCustomerPhone::class)->execute($customer, '123456'))
        ->toThrow(ValidationException::class);
});

test('each phone verification attempt gets its own UChat delivery', function (): void {
    config([
        'byruhaa.uchat.webhook_url' => 'https://uchat.test/store',
        'byruhaa.uchat.webhook_bearer_token' => 'outbound-secret',
        'byruhaa.uchat.webhook_signing_secret' => 'signing-secret',
    ]);
    Queue::fake();
    $customer = Customer::factory()->create();
    $sender = app(ByruhaaWebhookSender::class);

    $sender->sendUchatCustomerPhoneVerificationCode($customer, '111111', now()->addMinutes(10), 'verification-1');
    $sender->sendUchatCustomerPhoneVerificationCode($customer, '222222', now()->addMinutes(10), 'verification-2');

    expect(WebhookDelivery::query()
        ->where('event', 'customer.phone.verification_code')
        ->whereMorphedTo('webhookable', $customer)
        ->count())->toBe(2);
});
