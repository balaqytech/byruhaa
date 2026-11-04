<?php

namespace App\Modules\Finance\Actions;

use App\Enums\LedgerAccountType;
use App\Modules\Finance\Enums\WalletMovementType;
use App\Modules\Finance\Enums\WalletTopUpStatus;
use App\Modules\Finance\Events\WalletMovementPosted;
use App\Modules\Finance\Models\LedgerAccount;
use App\Modules\Finance\Models\Wallet;
use App\Modules\Finance\Models\WalletMovement;
use App\Modules\Finance\Models\WalletTopUp;
use App\Modules\Finance\Models\WalletWithdrawalRequest;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageWalletWithdrawal
{
    public function __construct(private PostFinancialEvent $postFinancialEvent) {}

    public function request(int $minorProfileId, int $guardianId, int $amountBaisa, string $operationKey, string $method, ?string $bankAccount = null, ?string $bankName = null): WalletWithdrawalRequest
    {
        if ($amountBaisa < 1 || ! in_array($method, ['cash', 'bank'], true) || ($method === 'bank' && (blank($bankAccount) || blank($bankName)))) {
            throw ValidationException::withMessages(['withdrawal' => 'Invalid withdrawal amount or payout method.']);
        }

        return DB::transaction(function () use ($minorProfileId, $guardianId, $amountBaisa, $operationKey, $method, $bankAccount, $bankName): WalletWithdrawalRequest {
            $guardian = Customer::query()->whereKey($guardianId)->firstOrFail();
            $wallet = Wallet::query()->where('minor_profile_id', $minorProfileId)->lockForUpdate()->firstOrFail();
            if ((int) $wallet->minorProfile->familyMember->customer_id !== $guardianId) {
                throw ValidationException::withMessages(['withdrawal' => 'لا تملك هذه المحفظة.']);
            }
            if ($wallet->status === 'closed') {
                throw ValidationException::withMessages(['withdrawal' => 'المحفظة مغلقة ولا يمكن طلب السحب منها.']);
            }
            if (! $guardian->hasVerifiedPhone()) {
                throw ValidationException::withMessages(['withdrawal' => 'يجب توثيق رقم هاتف وليّ الأمر قبل طلب السحب من المحفظة.']);
            }

            $existing = WalletWithdrawalRequest::query()->where('operation_key', $operationKey)->first();
            if ($existing instanceof WalletWithdrawalRequest) {
                if ($existing->wallet_id !== $wallet->id || $existing->guardian_customer_id !== $guardianId || $existing->amount_baisa !== $amountBaisa || $existing->method !== $method) {
                    throw ValidationException::withMessages(['withdrawal' => 'Operation key was already used for another request.']);
                }

                return $existing;
            }

            $topUps = WalletTopUp::query()
                ->where('wallet_id', $wallet->id)
                ->whereIn('status', [WalletTopUpStatus::Credited->value, WalletTopUpStatus::Refunding->value])
                ->orderBy('credited_at')->orderBy('id')->lockForUpdate()->get();
            $available = $topUps->sum(fn (WalletTopUp $topUp): int => max(0, $topUp->spendable_baisa - $topUp->reserved_refund_baisa - $topUp->reserved_withdrawal_baisa));
            if ($available < $amountBaisa || $wallet->balance_baisa < $amountBaisa) {
                throw ValidationException::withMessages(['amount' => 'Insufficient available wallet balance.']);
            }

            $withdrawal = WalletWithdrawalRequest::query()->create([
                'wallet_id' => $wallet->id,
                'guardian_customer_id' => $guardianId,
                'operation_key' => $operationKey,
                'amount_baisa' => $amountBaisa,
                'currency' => $wallet->currency,
                'status' => 'pending',
                'method' => $method,
                'bank_account' => $method === 'bank' ? $bankAccount : null,
                'bank_name' => $method === 'bank' ? $bankName : null,
                'bank_beneficiary_name' => $method === 'bank' ? $guardian->name : null,
            ]);

            $remaining = $amountBaisa;
            foreach ($topUps as $topUp) {
                if ($remaining === 0) {
                    break;
                }
                $allocation = min($remaining, max(0, $topUp->spendable_baisa - $topUp->reserved_refund_baisa - $topUp->reserved_withdrawal_baisa));
                if ($allocation === 0) {
                    continue;
                }
                $topUp->forceFill(['reserved_withdrawal_baisa' => $topUp->reserved_withdrawal_baisa + $allocation])->save();
                $withdrawal->allocations()->create(['wallet_top_up_id' => $topUp->id, 'amount_baisa' => $allocation]);
                $remaining -= $allocation;
            }

            return $withdrawal;
        });
    }

    public function approve(WalletWithdrawalRequest $withdrawal, int $actorUserId, ?string $note = null): WalletWithdrawalRequest
    {
        $this->assertStaff($actorUserId);

        return DB::transaction(function () use ($withdrawal, $actorUserId, $note): WalletWithdrawalRequest {
            $withdrawal = WalletWithdrawalRequest::query()->whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();
            if ($withdrawal->status !== 'pending') {
                throw ValidationException::withMessages(['withdrawal' => 'Only pending withdrawals can be approved.']);
            }

            $withdrawal->forceFill(['status' => 'approved', 'approved_by_user_id' => $actorUserId, 'approved_at' => now(), 'review_note' => $note])->save();

            return $withdrawal;
        });
    }

    public function reject(WalletWithdrawalRequest $withdrawal, int $actorUserId, string $reason): WalletWithdrawalRequest
    {
        $this->assertStaff($actorUserId);

        return $this->release($withdrawal, 'rejected', $actorUserId, $reason);
    }

    public function cancel(WalletWithdrawalRequest $withdrawal, int $guardianId): WalletWithdrawalRequest
    {
        if ($withdrawal->guardian_customer_id !== $guardianId) {
            throw ValidationException::withMessages(['withdrawal' => 'This request does not belong to the guardian.']);
        }

        return $this->release($withdrawal, 'cancelled', null, 'Cancelled by guardian.');
    }

    public function startPayout(WalletWithdrawalRequest $withdrawal, int $actorUserId): WalletWithdrawalRequest
    {
        $this->assertStaff($actorUserId);

        return DB::transaction(function () use ($withdrawal, $actorUserId): WalletWithdrawalRequest {
            $withdrawal = WalletWithdrawalRequest::query()->whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();
            if ($withdrawal->status !== 'approved' || $withdrawal->approved_by_user_id === null) {
                throw ValidationException::withMessages(['withdrawal' => 'The withdrawal must be approved before payout.']);
            }
            $withdrawal->forceFill(['status' => 'paying', 'paid_by_user_id' => $actorUserId])->save();

            return $withdrawal;
        });
    }

    public function markManualReview(WalletWithdrawalRequest $withdrawal, int $actorUserId, string $reason): WalletWithdrawalRequest
    {
        $this->assertStaff($actorUserId);

        return DB::transaction(function () use ($withdrawal, $actorUserId, $reason): WalletWithdrawalRequest {
            $withdrawal = WalletWithdrawalRequest::query()->whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();
            if ($withdrawal->status !== 'paying' || blank($reason)) {
                throw ValidationException::withMessages(['withdrawal' => 'Only a payout in progress can enter manual review.']);
            }
            $withdrawal->forceFill(['status' => 'manual_review', 'review_note' => $reason, 'paid_by_user_id' => $actorUserId])->save();

            return $withdrawal;
        });
    }

    public function completePayout(WalletWithdrawalRequest $withdrawal, int $actorUserId, string $payoutReference, ?string $evidencePath = null, bool $beneficiaryVerified = false): WalletWithdrawalRequest
    {
        $this->assertStaff($actorUserId);
        $postedMovement = null;
        $result = DB::transaction(function () use ($withdrawal, $actorUserId, $payoutReference, $evidencePath, $beneficiaryVerified, &$postedMovement): WalletWithdrawalRequest {
            $withdrawal = WalletWithdrawalRequest::query()->whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();
            if ($withdrawal->status === 'paid' && $withdrawal->payout_reference === $payoutReference) {
                return $withdrawal;
            }
            if (! in_array($withdrawal->status, ['paying', 'manual_review'], true) || blank($payoutReference) || ($withdrawal->method === 'bank' && (blank($evidencePath) || ! $beneficiaryVerified))) {
                throw ValidationException::withMessages(['withdrawal' => 'Payout reference and bank evidence are required before completion.']);
            }

            $wallet = Wallet::query()->whereKey($withdrawal->wallet_id)->lockForUpdate()->firstOrFail();
            $allocations = $withdrawal->allocations()->with('topUp')->orderBy('id')->lockForUpdate()->get();
            foreach ($allocations as $allocation) {
                $topUp = WalletTopUp::query()->whereKey($allocation->wallet_top_up_id)->lockForUpdate()->firstOrFail();
                if ($topUp->reserved_withdrawal_baisa < $allocation->amount_baisa || $topUp->spendable_baisa < $allocation->amount_baisa) {
                    throw ValidationException::withMessages(['withdrawal' => 'Reserved wallet funds are inconsistent.']);
                }
                $topUp->forceFill([
                    'reserved_withdrawal_baisa' => $topUp->reserved_withdrawal_baisa - $allocation->amount_baisa,
                    'spendable_baisa' => $topUp->spendable_baisa - $allocation->amount_baisa,
                    'refundable_baisa' => max(0, $topUp->refundable_baisa - $allocation->amount_baisa),
                ])->save();
            }
            if ($wallet->balance_baisa < $withdrawal->amount_baisa) {
                throw ValidationException::withMessages(['withdrawal' => 'Wallet balance is inconsistent with the reserved amount.']);
            }
            $wallet->forceFill(['balance_baisa' => $wallet->balance_baisa - $withdrawal->amount_baisa])->save();
            $postedMovement = $wallet->movements()->create([
                'operation_key' => 'wallet-withdrawal:'.$withdrawal->id,
                'type' => WalletMovementType::Withdrawal,
                'credit_baisa' => 0,
                'debit_baisa' => $withdrawal->amount_baisa,
                'balance_after_baisa' => $wallet->balance_baisa,
                'metadata' => ['withdrawal_reference' => $withdrawal->reference, 'payout_reference' => $payoutReference],
            ]);
            $withdrawal->forceFill(['status' => 'paid', 'paid_by_user_id' => $actorUserId, 'paid_at' => now(), 'payout_reference' => $payoutReference, 'evidence_path' => $evidencePath])->save();

            $creditCode = $withdrawal->method === 'cash' ? LedgerAccount::CASH_ON_HAND_CODE : LedgerAccount::BANK_CODE;
            $this->postFinancialEvent->execute(
                'wallet-withdrawal:'.$withdrawal->id,
                'wallet_withdrawal',
                'wallet_withdrawal',
                $withdrawal->id,
                'Wallet withdrawal '.$withdrawal->reference,
                $withdrawal->currency,
                $withdrawal->paid_at,
                [
                    ['code' => LedgerAccount::WALLET_LIABILITY_CODE, 'name' => 'Wallet liability', 'type' => LedgerAccountType::Liability, 'debit' => $withdrawal->amount_baisa, 'credit' => 0],
                    ['code' => $creditCode, 'name' => $withdrawal->method === 'cash' ? 'Cash on hand' : 'Bank', 'type' => LedgerAccountType::Asset, 'debit' => 0, 'credit' => $withdrawal->amount_baisa],
                ],
            );

            return $withdrawal;
        });
        if ($postedMovement instanceof WalletMovement) {
            WalletMovementPosted::dispatch($postedMovement);
        }

        return $result;
    }

    private function release(WalletWithdrawalRequest $withdrawal, string $state, ?int $actorUserId, string $note): WalletWithdrawalRequest
    {
        return DB::transaction(function () use ($withdrawal, $state, $actorUserId, $note): WalletWithdrawalRequest {
            $withdrawal = WalletWithdrawalRequest::query()->whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();
            if ($withdrawal->status !== 'pending') {
                throw ValidationException::withMessages(['withdrawal' => 'Only pending withdrawals can be released.']);
            }
            Wallet::query()->whereKey($withdrawal->wallet_id)->lockForUpdate()->firstOrFail();
            foreach ($withdrawal->allocations()->orderBy('id')->lockForUpdate()->get() as $allocation) {
                $topUp = WalletTopUp::query()->whereKey($allocation->wallet_top_up_id)->lockForUpdate()->firstOrFail();
                $topUp->forceFill(['reserved_withdrawal_baisa' => $topUp->reserved_withdrawal_baisa - $allocation->amount_baisa])->save();
            }
            $withdrawal->forceFill(['status' => $state, 'review_note' => $note, 'approved_by_user_id' => $actorUserId])->save();

            return $withdrawal;
        });
    }

    private function assertStaff(int $actorUserId): void
    {
        $actor = User::query()->findOrFail($actorUserId);
        if (! $actor->isPanelAdministrator() && ! $actor->can('Update:WalletWithdrawalRequest')) {
            throw ValidationException::withMessages(['withdrawal' => 'This employee cannot manage Wallet withdrawals.']);
        }
    }
}
