<?php

use App\Modules\Finance\Contracts\WalletService;
use App\Modules\Finance\Models\WalletTopUp;
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
use App\Modules\Store\States\Order\Confirmed;
use App\Services\Webhooks\ByruhaaWebhookSender;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\mock;

test('minor login is discoverable from the public footer and customer login form', function (): void {
    config(['byruhaa.minor_accounts.enabled' => true]);
    $this->get(route('home'))->assertOk()->assertSee('href="'.route('minor.login').'"', false);
    $response = $this->get(route('login'))->assertOk()->assertSee('للدخول برمز العضوية وكلمة المرور بعد تفعيل الحساب.');
    expect(substr_count($response->getContent(), 'href="'.route('minor.login').'"'))->toBe(2);
    $this->get(route('minor.login'))->assertOk()->assertSee('name="member_code"', false);
});

test('minor login links are hidden when minor accounts are disabled', function (): void {
    config(['byruhaa.minor_accounts.enabled' => false]);
    $this->get(route('login'))->assertOk()->assertDontSee('href="'.route('minor.login').'"', false);
});

test('minor profiles page hides new-member birth date when an existing member is selected', function (): void {
    $customer = Customer::factory()->create();
    FamilyMember::factory()->for($customer)->create(['name' => 'Existing Member']);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.minor-profiles.index'))
        ->assertOk()
        ->assertSee('href="'.route('minor.login').'"', false)
        ->assertSee('x-model="familyMemberId"', false)
        ->assertSee('x-show="! familyMemberId"', false)
        ->assertSee('w-full', false);
});

test('guardian can create and activate a minor profile linked to an existing family member', function () {
    $customer = Customer::factory()->create();
    $familyMember = FamilyMember::factory()->for($customer)->create();
    mock(ByruhaaWebhookSender::class)->shouldNotReceive('sendUchatMinorVerificationCode');

    $this->actingAs($customer, 'customer')
        ->post(route('customer.minor-profiles.store'), ['family_member_id' => $familyMember->id])
        ->assertRedirect(route('customer.minor-profiles.index'));

    $profile = MinorProfile::query()->firstOrFail();
    $activationUrl = session('activation_url');
    expect($profile->family_member_id)->toBe($familyMember->id)
        ->and($profile->status)->toBe(MinorProfileStatus::PendingChildActivation)
        ->and($profile->verifications()->count())->toBe(0)
        ->and($profile->consents()->where('purpose', 'store_purchase')->exists())->toBeTrue()
        ->and($activationUrl)->toBeString()
        ->and($customer->refresh()->hasVerifiedPhone())->toBeFalse();

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

test('existing family member ignores stale new-member fields', function (): void {
    $customer = Customer::factory()->create();
    $familyMember = FamilyMember::factory()->for($customer)->create();

    $this->actingAs($customer, 'customer')
        ->post(route('customer.minor-profiles.store'), [
            'family_member_id' => $familyMember->id,
            'name' => 'stale name',
            'birth_date' => 'not-a-date',
        ])
        ->assertRedirect(route('customer.minor-profiles.index'))
        ->assertSessionHasNoErrors();

    expect(MinorProfile::query()->where('family_member_id', $familyMember->id)->exists())->toBeTrue();
});

test('guardian can renew activation for pending profiles without OTP and older links stop working', function (MinorProfileStatus $status): void {
    $customer = Customer::factory()->create();
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create(['status' => $status]);
    $verification = $profile->verifications()->create(['code_hash' => Hash::make('123456'), 'expires_at' => now()->addMinutes(10)]);
    $url = route('customer.minor-profiles.activation-link', $profile);
    $this->actingAs($customer, 'customer')->post($url)->assertRedirect(route('customer.minor-profiles.index'));
    $first = session('activation_url');
    $this->post($url)->assertRedirect(route('customer.minor-profiles.index'));
    $second = session('activation_url');
    expect($profile->refresh()->status)->toBe(MinorProfileStatus::PendingChildActivation)
        ->and($profile->consents()->count())->toBe(1)
        ->and($verification->refresh()->consumed_at)->not->toBeNull()
        ->and($profile->direct_payment_enabled)->toBeFalse()
        ->and($profile->wallet_spending_enabled)->toBeFalse();
    $this->get($first)->assertNotFound();
    $this->get($second)->assertOk();
    $this->travel(25)->hours();
    $this->get($second)->assertForbidden();
})->with([MinorProfileStatus::PendingGuardianVerification, MinorProfileStatus::PendingChildActivation]);

test('activation link cannot change active or restricted accounts', function (MinorProfileStatus $status): void {
    $customer = Customer::factory()->create();
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create(['status' => $status]);
    $this->actingAs($customer, 'customer')->post(route('customer.minor-profiles.activation-link', $profile))
        ->assertSessionHasErrors('minor_profile_id');
    expect($profile->refresh()->status)->toBe($status)->and($profile->activation_token_hash)->toBeNull();
})->with([MinorProfileStatus::Active, MinorProfileStatus::Suspended, MinorProfileStatus::DeletionRequested, MinorProfileStatus::Invalidated]);

test('activation links require the owning guardian and enabled minor accounts', function (): void {
    $customer = Customer::factory()->create();
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create(['status' => MinorProfileStatus::PendingChildActivation]);
    $this->actingAs(Customer::factory()->create(), 'customer')->post(route('customer.minor-profiles.activation-link', $profile))->assertNotFound();
    config(['byruhaa.minor_accounts.enabled' => false]);
    $this->actingAs($customer, 'customer')->post(route('customer.minor-profiles.activation-link', $profile))->assertSessionHasErrors('minor_accounts');
    expect($profile->refresh()->activation_token_hash)->toBeNull();
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

test('minor order payment controls respect the selected method and guardian permissions', function (string $method, bool $direct, bool $wallet, bool $verified, bool $enabled, ?string $reason, string $button): void {
    config(['byruhaa.wallets.enabled' => $enabled]);
    $customer = Customer::factory()->create(['phone_verified_at' => $verified ? now() : null]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create([
        'direct_payment_enabled' => $direct,
        'wallet_spending_enabled' => $wallet,
    ]);
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'minor_profile_id' => $profile->id,
        'payment_method' => $method,
    ]);
    $url = route('minor.orders.show', $order->payment_token);
    $response = $this->actingAs($profile, 'minor-profile')->get($url)->assertOk();

    if ($reason !== null) {
        $response->assertSeeText($reason)->assertDontSee('action="'.route('minor.orders.payment', $order->payment_token).'"', false);
        $this->from($url)->post(route('minor.orders.payment', $order->payment_token))
            ->assertRedirect($url)->assertSessionHasErrors('payment');
        expect(session('errors')->first('payment'))->toContain($reason);
        expect($order->refresh()->paid_at)->toBeNull();
    } else {
        $response->assertSeeText($button)->assertSee('action="'.route('minor.orders.payment', $order->payment_token).'"', false);
    }
})->with([
    'wallet permission does not authorize Thawani' => ['thawani', false, true, true, true, 'هذا الطلب للدفع عبر ثواني.', 'الدفع عبر ثواني'],
    'Thawani permission allows its button' => ['thawani', true, false, false, false, null, 'الدفع عبر ثواني'],
    'wallet does not need direct payment permission' => ['wallet', false, true, true, true, null, 'الدفع من المحفظة'],
    'wallet permission is required' => ['wallet', true, false, true, true, 'يلزم أن يفعّل وليّ الأمر «السماح بالدفع من المحفظة» من حسابه.', 'الدفع من المحفظة'],
    'verified phone is required' => ['wallet', true, true, false, true, 'يلزم توثيق هاتف وليّ الأمر قبل الدفع من المحفظة.', 'الدفع من المحفظة'],
    'wallet feature must be enabled' => ['wallet', true, true, true, false, 'الدفع بالمحفظة غير متاح حاليًا.', 'الدفع من المحفظة'],
]);

test('enabling wallet spending records a wallet consent', function (): void {
    config(['byruhaa.wallets.enabled' => true]);
    $customer = Customer::factory()->create(['phone_verified_at' => now()]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create([
        'wallet_spending_enabled' => false,
    ]);

    $this->actingAs($customer, 'customer')
        ->post(route('customer.minor-profiles.wallet-spending', $profile))
        ->assertRedirect();

    $consent = $profile->consents()->where('purpose', 'wallet_spending')->firstOrFail();

    expect($profile->refresh()->wallet_spending_enabled)->toBeTrue()
        ->and($consent->policy_version)->toBe('wallet-spending-v1')
        ->and($consent->policy_hash)->toBe(hash('sha256', 'guardian-consent-wallet-spending'));
});

test('account deletion is blocked while the child wallet has funds or pending top ups', function (): void {
    config(['byruhaa.wallets.enabled' => true]);
    $customer = Customer::factory()->create();
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create();
    $wallet = app(WalletService::class)->walletForMinorProfile($profile->id);
    $wallet->forceFill(['balance_baisa' => 1000])->save();

    $this->actingAs($customer, 'customer')
        ->post(route('customer.minor-profiles.delete-request', $profile))
        ->assertSessionHasErrors('wallet');

    $wallet->forceFill(['balance_baisa' => 0])->save();
    WalletTopUp::query()->create([
        'wallet_id' => $wallet->id,
        'operation_key' => 'pending-deletion-top-up',
        'status' => 'pending',
        'currency' => 'OMR',
        'amount_baisa' => 5000,
    ]);

    $this->actingAs($customer, 'customer')
        ->post(route('customer.minor-profiles.delete-request', $profile))
        ->assertSessionHasErrors('wallet');

    expect($profile->refresh()->status)->toBe(MinorProfileStatus::Active);
});

test('account deletion remains blocked by wallet funds when wallets are disabled', function (): void {
    config(['byruhaa.wallets.enabled' => true]);
    $customer = Customer::factory()->create();
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create();
    $wallet = app(WalletService::class)->walletForMinorProfile($profile->id);
    $wallet->forceFill(['balance_baisa' => 1000])->save();
    config(['byruhaa.wallets.enabled' => false]);

    $this->actingAs($customer, 'customer')
        ->post(route('customer.minor-profiles.delete-request', $profile))
        ->assertSessionHasErrors('wallet');

    expect($profile->refresh()->status)->toBe(MinorProfileStatus::Active);
});

test('account deletion is blocked while a paid wallet order remains unfulfilled', function (): void {
    config(['byruhaa.wallets.enabled' => true]);
    $customer = Customer::factory()->create();
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($customer))->create();
    $walletService = app(WalletService::class);
    $wallet = $walletService->walletForMinorProfile($profile->id);
    $topUp = WalletTopUp::query()->create([
        'wallet_id' => $wallet->id,
        'operation_key' => 'paid-order-closure-check',
        'status' => 'credited',
        'currency' => 'OMR',
        'amount_baisa' => 1000,
        'refundable_baisa' => 1000,
        'spendable_baisa' => 1000,
        'credited_at' => now(),
        'refund_deadline_at' => now()->addDay(),
    ]);
    $wallet->increment('balance_baisa', $topUp->amount_baisa);
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'minor_profile_id' => $profile->id,
        'payment_method' => 'wallet',
        'status' => Confirmed::$name,
        'total_baisa' => 1000,
        'paid_at' => now(),
    ]);

    $walletService->spend($profile->id, $order->reference, 1000, 'OMR');

    $this->actingAs($customer, 'customer')
        ->post(route('customer.minor-profiles.delete-request', $profile))
        ->assertSessionHasErrors('wallet');

    expect($profile->refresh()->status)->toBe(MinorProfileStatus::Active);
});
