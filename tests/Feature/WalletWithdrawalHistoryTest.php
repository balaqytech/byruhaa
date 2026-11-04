<?php

use App\Filament\Resources\WalletWithdrawals\WalletWithdrawalResource;
use App\Modules\Finance\Actions\ManageWalletWithdrawal;
use App\Modules\Finance\Contracts\WalletService;
use App\Modules\Finance\Models\WalletTopUp;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Identity\Models\User;

test('staff can view the withdrawal audit history with actors and changes', function (): void {
    config(['audit.console' => true]);
    $guardian = Customer::factory()->create(['phone_verified_at' => now()]);
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian))->create();
    $staff = User::factory()->admin()->create();
    $wallet = app(WalletService::class)->walletForMinorProfile($profile->id);
    WalletTopUp::query()->create([
        'wallet_id' => $wallet->id,
        'operation_key' => 'history-top-up',
        'status' => 'credited',
        'currency' => 'OMR',
        'amount_baisa' => 5000,
        'spendable_baisa' => 5000,
        'refundable_baisa' => 0,
        'credited_at' => now(),
    ]);
    $wallet->increment('balance_baisa', 5000);

    $withdrawals = app(ManageWalletWithdrawal::class);
    $this->actingAs($guardian, 'customer');
    $withdrawal = $withdrawals->request($profile->id, $guardian->id, 2000, 'history-withdrawal', 'cash');
    $this->actingAs($staff, 'web');
    $withdrawals->approve($withdrawal, $staff->id, 'تمت مراجعة الطلب');
    $withdrawals->startPayout($withdrawal, $staff->id);
    $withdrawals->completePayout($withdrawal, $staff->id, 'CASH-RECEIPT-1', 'withdrawal-evidence/receipt.pdf');

    expect($withdrawal->audits()->count())->toBe(4);
    $this->get(WalletWithdrawalResource::getUrl('view', ['record' => $withdrawal]))
        ->assertOk()
        ->assertSeeText('سجل التغييرات')
        ->assertSeeText('إنشاء الطلب')
        ->assertSeeText('ولي الأمر: '.$guardian->name)
        ->assertSeeText('الموظف: '.$staff->name)
        ->assertSeeText('تغيير الحالة إلى موافق عليه')
        ->assertSeeText('تغيير الحالة إلى قيد الصرف')
        ->assertSeeText('تغيير الحالة إلى تم الصرف')
        ->assertSeeText('إثبات الصرف: مرفق')
        ->assertSeeText('CASH-RECEIPT-1')
        ->assertDontSee('withdrawal-evidence/receipt.pdf')
        ->assertSeeInOrder(['تغيير الحالة إلى تم الصرف', 'تغيير الحالة إلى قيد الصرف', 'تغيير الحالة إلى موافق عليه', 'إنشاء الطلب'])
        ->assertSeeText('الحالة: من قيد المراجعة إلى موافق عليه')
        ->assertSeeText('ملاحظة المراجعة: تمت مراجعة الطلب');

    $this->actingAs(User::factory()->create(), 'web')
        ->get(WalletWithdrawalResource::getUrl('view', ['record' => $withdrawal]))
        ->assertForbidden();
});
