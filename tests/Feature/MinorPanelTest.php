<?php

use App\Modules\Finance\Models\Wallet;
use App\Modules\Finance\Models\WalletTopUp;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Store\Actions\ConfirmWalletOrder;
use App\Modules\Store\Models\Order;
use Illuminate\Validation\ValidationException;

test('minor order details show a translated history table newest first for the owned order only', function (): void {
    app()->setLocale('ar');
    $profile = MinorProfile::factory()->create();
    $order = Order::factory()->create(['minor_profile_id' => $profile->id]);
    $order->statusHistory()->create(['from_status' => null, 'to_status' => 'pending_payment']);
    $order->statusHistory()->create(['from_status' => 'pending_payment', 'to_status' => 'confirmed', 'note' => 'Wallet payment confirmed.']);
    $order->statusHistory()->create(['from_status' => 'confirmed', 'to_status' => 'preparing', 'note' => 'بدأ تحضير القهوة']);
    $otherOrder = Order::factory()->create(['minor_profile_id' => MinorProfile::factory()->create()->id]);
    $otherOrder->statusHistory()->create(['from_status' => null, 'to_status' => 'pending_payment', 'note' => 'Private other order history']);

    $this->actingAs($profile, 'minor-profile')->get(route('minor.orders.show', $order->payment_token))
        ->assertOk()->assertSee('<table', false)->assertSee('سجل تغيّرات حالة الطلب')
        ->assertSee('الحالة السابقة')->assertSee('الحالة الجديدة')->assertSee('التاريخ والوقت')
        ->assertSeeInOrder(['بدأ تحضير القهوة', __('Wallet payment confirmed.'), 'إنشاء الطلب'])
        ->assertSee('قيد التحضير')->assertSee('بانتظار الدفع')
        ->assertSee($order->statusHistory()->first()->created_at->format('Y-m-d H:i'))
        ->assertDontSee('Wallet payment confirmed.')->assertDontSee('Private other order history');
    $this->get(route('minor.orders.show', $otherOrder->payment_token))->assertNotFound();
});

test('minor order details show an empty history message when no changes are recorded', function (): void {
    $profile = MinorProfile::factory()->create();
    $order = Order::factory()->create(['minor_profile_id' => $profile->id]);
    $this->actingAs($profile, 'minor-profile')->get(route('minor.orders.show', $order->payment_token))
        ->assertOk()->assertSee('لا توجد تغيّرات مسجّلة لهذا الطلب بعد.');
});

test('minor dashboard shows only its own wallet balance movements and orders', function (): void {
    app()->setLocale('ar');
    config(['byruhaa.wallets.enabled' => true]);
    $guardian = Customer::factory()->create(['phone_verified_at' => now()]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian))->create(['wallet_spending_enabled' => true]);
    $wallet = Wallet::query()->create(['minor_profile_id' => $profile->id, 'balance_baisa' => 5000]);
    WalletTopUp::query()->create([
        'wallet_id' => $wallet->id, 'operation_key' => 'panel-topup', 'status' => 'refunding',
        'amount_baisa' => 5000, 'spendable_baisa' => 5000, 'reserved_refund_baisa' => 1000, 'currency' => 'OMR',
    ]);
    $movement = $wallet->movements()->create([
        'operation_key' => 'panel-movement', 'type' => 'top_up', 'credit_baisa' => 5000, 'debit_baisa' => 0, 'balance_after_baisa' => 5000,
    ]);
    $otherProfile = MinorProfile::factory()->create();
    Wallet::query()->create(['minor_profile_id' => $otherProfile->id, 'balance_baisa' => 99000]);
    $ownOrder = Order::factory()->create(['minor_profile_id' => $profile->id, 'customer_id' => $guardian->id]);
    $otherOrder = Order::factory()->create(['minor_profile_id' => $otherProfile->id]);

    $response = $this->actingAs($profile, 'minor-profile')
        ->get(route('minor.dashboard', ['minor_profile_id' => $otherProfile->id]))
        ->assertOk()->assertSee('محفظتي')->assertSee('4.000')->assertSee('محجوز للاسترداد')
        ->assertSee('شحن المحفظة')->assertSee('بانتظار الدفع')->assertSee($ownOrder->reference)
        ->assertDontSee($otherOrder->reference)->assertDontSee('99.000');
    expect($response->viewData('wallet')->id)->toBe($wallet->id)
        ->and($response->viewData('wallet')->movements->modelKeys())->toBe([$movement->id])
        ->and($response->viewData('reservedBalance'))->toBe(1000);
});

test('minor panel respects disabled wallets and spending permission', function (bool $enabled): void {
    config(['byruhaa.wallets.enabled' => $enabled]);
    $profile = MinorProfile::factory()->create(['wallet_spending_enabled' => false]);
    $response = $this->actingAs($profile, 'minor-profile')->get(route('minor.dashboard'))->assertOk();
    if ($enabled) {
        $response->assertSee('محفظتي')->assertSee('الدفع من المحفظة غير مفعّل')->assertSee('0.000');
    } else {
        $response->assertDontSee('id="wallet"', false);
        $this->get(route('minor.wallet.movements.index'))->assertNotFound();
        expect(Wallet::query()->where('minor_profile_id', $profile->id)->exists())->toBeFalse();
    }
})->with([true, false]);

test('minor wallet movement history uses cursor pagination and excludes other wallets', function (): void {
    config(['byruhaa.wallets.enabled' => true]);
    $profile = MinorProfile::factory()->create();
    $wallet = Wallet::query()->create(['minor_profile_id' => $profile->id, 'balance_baisa' => 25_000]);

    foreach (range(1, 25) as $number) {
        $wallet->movements()->create([
            'operation_key' => 'cursor-movement-'.$number,
            'type' => 'top_up',
            'order_reference' => sprintf('MOVE-%02d', $number),
            'credit_baisa' => 1000,
            'debit_baisa' => 0,
            'balance_after_baisa' => $number * 1000,
        ]);
    }

    $otherProfile = MinorProfile::factory()->create();
    $otherWallet = Wallet::query()->create(['minor_profile_id' => $otherProfile->id, 'balance_baisa' => 99_000]);
    $otherWallet->movements()->create([
        'operation_key' => 'other-wallet-movement',
        'type' => 'top_up',
        'order_reference' => 'PRIVATE-MOVEMENT',
        'credit_baisa' => 99_000,
        'debit_baisa' => 0,
        'balance_after_baisa' => 99_000,
    ]);

    $response = $this->actingAs($profile, 'minor-profile')
        ->get(route('minor.wallet.movements.index'))
        ->assertOk()
        ->assertSee('MOVE-25')
        ->assertDontSee('MOVE-01')
        ->assertDontSee('PRIVATE-MOVEMENT');

    $movements = $response->viewData('movements');
    expect($movements->count())->toBe(20)
        ->and($movements->hasMorePages())->toBeTrue();

    $this->get($movements->nextPageUrl())
        ->assertOk()
        ->assertSee('MOVE-01')
        ->assertDontSee('PRIVATE-MOVEMENT');
});

test('guardian minor cards and wallet forms render accessible fields and Arabic messages', function (): void {
    app()->setLocale('ar');
    config(['byruhaa.wallets.enabled' => true]);
    $guardian = Customer::factory()->create(['phone_verified_at' => now()]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian))->create();
    $this->actingAs($guardian, 'customer')->get(route('customer.minor-profiles.index'))
        ->assertOk()->assertSee('نشط')->assertSee('data-flux-select', false)
        ->assertSee('data-flux-input', false)->assertSee('الدفع المباشر عبر ثواني')
        ->assertSee('x-bind:disabled="!! familyMemberId"', false);
    $this->withSession(['wallet_status' => 'cancelled'])->get(route('customer.minor-profiles.wallet', $profile))
        ->assertOk()->assertSee('ملغاة')->assertSee('name="amount_omr"', false)->assertSee('data-flux-input', false);
});

test('minor login uses visible shared inputs', function (): void {
    app()->setLocale('ar');
    $this->get(route('minor.login'))->assertOk()->assertSee('data-flux-input', false)
        ->assertSee('autocomplete="username"', false)->assertSee('autocomplete="current-password"', false);
});

test('guardian form displays translated domain errors inline as well as in the alert', function (): void {
    app()->setLocale('ar');
    $guardian = Customer::factory()->create();
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian))->create();
    $url = route('customer.minor-profiles.index');
    $this->actingAs($guardian, 'customer')->from($url)
        ->post(route('customer.minor-profiles.store'), [
            'family_member_id' => $profile->family_member_id,
            'browser_notifications_consent' => '1',
        ])
        ->assertRedirect($url);
    $response = $this->get($url)->assertOk()
        ->assertSee('يوجد حساب قاصر لفرد الأسرة المحدد بالفعل.')
        ->assertDontSee('This family member already has a minor account.');
    expect(substr_count($response->getContent(), 'يوجد حساب قاصر لفرد الأسرة المحدد بالفعل.'))->toBeGreaterThanOrEqual(2);
});

test('minor order page translates existing payment errors', function (): void {
    app()->setLocale('ar');
    config(['byruhaa.wallets.enabled' => true]);
    $guardian = Customer::factory()->create(['phone_verified_at' => now()]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian))->create(['wallet_spending_enabled' => true]);
    $order = Order::factory()->create(['minor_profile_id' => $profile->id, 'customer_id' => $guardian->id, 'payment_method' => 'wallet']);
    $this->mock(ConfirmWalletOrder::class)
        ->shouldReceive('execute')->once()
        ->andThrow(ValidationException::withMessages(['payment' => 'The inventory reservation is no longer available.']));
    $url = route('minor.orders.show', $order->payment_token);
    $this->actingAs($profile, 'minor-profile')->from($url)
        ->post(route('minor.orders.payment', $order->payment_token))->assertRedirect($url);
    $this->get($url)->assertOk()
        ->assertSee('انتهت صلاحية حجز مخزون الطلب. يرجى إنشاء طلب جديد.')
        ->assertDontSee('The inventory reservation is no longer available.');
});
