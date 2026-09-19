<?php

use App\Filament\Resources\MinorProfiles\MinorProfileResource;
use App\Filament\Resources\MinorProfiles\Pages\ListMinorProfiles;
use App\Filament\Resources\MinorProfiles\Pages\ViewMinorProfile;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Wallets\Pages\ListWallets;
use App\Filament\Resources\Wallets\Pages\ViewWallet;
use App\Filament\Resources\Wallets\RelationManagers\MovementsRelationManager;
use App\Filament\Resources\Wallets\RelationManagers\TopUpsRelationManager;
use App\Filament\Resources\Wallets\WalletResource;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\Wallet;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Identity\Models\User;
use Livewire\Livewire;

test('staff can find minors and wallets even when customer wallets are disabled', function (): void {
    config(['byruhaa.wallets.enabled' => false]);
    $guardian = Customer::factory()->create(['name' => 'Wallet Guardian']);
    $minor = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian)->state(['name' => 'Wallet Child']))->create();
    $other = MinorProfile::factory()->create();
    $wallet = Wallet::query()->create(['minor_profile_id' => $minor->id, 'balance_baisa' => 5000]);
    $otherWallet = Wallet::query()->create(['minor_profile_id' => $other->id]);
    $this->actingAs(User::factory()->create(), 'web');

    $this->get(MinorProfileResource::getUrl('index'))->assertOk()->assertSee('Wallet Child');
    $this->get(WalletResource::getUrl('index'))->assertOk()->assertSee('Wallet Guardian');
    $this->get(WalletResource::getUrl('view', ['record' => $wallet]))->assertOk()->assertSee('Wallet Child');
    $this->get(MinorProfileResource::getUrl('view', ['record' => $minor]))->assertOk()->assertSee(WalletResource::getUrl('view', ['record' => $wallet]), false);

    Livewire::test(ListWallets::class)->searchTable($minor->member_code)->assertCanSeeTableRecords([$wallet])->assertCanNotSeeTableRecords([$otherWallet])->assertTableColumnStateSet('balance_baisa', 5000, $wallet);
    Livewire::test(ListMinorProfiles::class)->searchTable('Wallet Guardian')->assertCanSeeTableRecords([$minor])->assertCanNotSeeTableRecords([$other]);
    Livewire::test(ListMinorProfiles::class)->filterTable('status', 'suspended')->assertCanNotSeeTableRecords([$minor]);
});

test('viewing a minor without a wallet does not create one or expose mutations', function (): void {
    $minor = MinorProfile::factory()->create();
    $this->actingAs(User::factory()->admin()->create(), 'web');
    Livewire::test(ViewMinorProfile::class, ['record' => $minor->id])->assertSuccessful()->assertActionDoesNotExist('wallet');
    expect(Wallet::query()->count())->toBe(0)
        ->and(MinorProfileResource::canCreate())->toBeFalse()
        ->and(MinorProfileResource::canEdit($minor))->toBeFalse()
        ->and(MinorProfileResource::canDelete($minor))->toBeFalse()
        ->and(WalletResource::canCreate())->toBeFalse()
        ->and(WalletResource::canEdit(new Wallet))->toBeFalse()
        ->and(WalletResource::canDeleteAny())->toBeFalse();
    Livewire::test(ListWallets::class)->assertTableActionsExistInOrder(['view']);
});

test('wallet history is scoped and top ups show reserved and expired refund eligibility', function (): void {
    $minor = MinorProfile::factory()->create();
    $wallet = Wallet::query()->create(['minor_profile_id' => $minor->id, 'balance_baisa' => 5000]);
    $otherWallet = Wallet::query()->create(['minor_profile_id' => MinorProfile::factory()->create()->id]);
    $payment = Payment::factory()->create();
    $topUp = $wallet->topUps()->create([
        'operation_key' => 'admin-wallet-top-up', 'payment_id' => $payment->id,
        'amount_baisa' => 5000, 'spendable_baisa' => 4000, 'refundable_baisa' => 4000,
        'reserved_refund_baisa' => 1000, 'status' => 'refunding', 'currency' => 'OMR',
        'refund_deadline_at' => now()->addHour(),
    ]);
    $movement = $wallet->movements()->create([
        'operation_key' => 'admin-credit', 'wallet_top_up_id' => $topUp->id, 'type' => 'top_up',
        'credit_baisa' => 5000, 'debit_baisa' => 0, 'balance_after_baisa' => 5000,
    ]);
    $otherMovement = $otherWallet->movements()->create([
        'operation_key' => 'other-credit', 'type' => 'top_up',
        'credit_baisa' => 1000, 'debit_baisa' => 0, 'balance_after_baisa' => 1000,
    ]);
    $this->actingAs(User::factory()->create(), 'web');
    Livewire::test(MovementsRelationManager::class, ['ownerRecord' => $wallet, 'pageClass' => ViewWallet::class])
        ->assertCanSeeTableRecords([$movement])->assertCanNotSeeTableRecords([$otherMovement])
        ->assertTableHeaderActionsExistInOrder([])->assertTableActionsExistInOrder([]);
    Livewire::test(TopUpsRelationManager::class, ['ownerRecord' => $wallet, 'pageClass' => ViewWallet::class])
        ->assertCanSeeTableRecords([$topUp])
        ->assertTableColumnStateSet('available_baisa', 3000, $topUp)
        ->assertTableColumnStateSet('eligible_refund_baisa', 3000, $topUp)
        ->assertSee(PaymentResource::getUrl('view', ['record' => $payment]), false);
    $topUp->update(['refund_deadline_at' => now()->subMinute()]);
    Livewire::test(TopUpsRelationManager::class, ['ownerRecord' => $wallet, 'pageClass' => ViewWallet::class])
        ->assertTableColumnStateSet('eligible_refund_baisa', 0, $topUp)
        ->assertTableColumnStateSet('available_baisa', 3000, $topUp);
});

test('guests and customer sessions cannot access admin wallet and minor pages', function (): void {
    $minor = MinorProfile::factory()->create();
    $wallet = Wallet::query()->create(['minor_profile_id' => $minor->id]);
    $urls = [
        WalletResource::getUrl('index'), WalletResource::getUrl('view', ['record' => $wallet]),
        MinorProfileResource::getUrl('index'), MinorProfileResource::getUrl('view', ['record' => $minor]),
    ];
    foreach ($urls as $url) {
        $this->get($url)->assertRedirect();
    }
    $this->actingAs(Customer::factory()->create(), 'customer');
    foreach ($urls as $url) {
        $this->get($url)->assertRedirect();
    }
    expect(WalletResource::canViewAny())->toBeFalse()->and(MinorProfileResource::canViewAny())->toBeFalse();
});
