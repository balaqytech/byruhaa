<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Exceptions\PaymentGatewayException;
use App\Http\Requests\Integrations\Uchat\UchatIdentityRequest;
use App\Http\Requests\Integrations\Uchat\UchatRequest;
use App\Modules\Finance\Actions\InitiateWalletTopUp;
use App\Modules\Finance\Contracts\WalletService;
use App\Modules\Finance\Http\Requests\UchatWalletTopUpRequest;
use App\Modules\Finance\Models\Wallet;
use App\Modules\Finance\Models\WalletTopUp;
use App\Modules\Identity\Contracts\MinorProfilePurchasing;
use App\Support\Money\MoneyFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class UchatWalletController
{
    public function __construct(private MinorProfilePurchasing $minorProfiles) {}

    public function wallet(UchatIdentityRequest $request, WalletService $wallets): JsonResponse
    {
        if (! config('byruhaa.wallets.enabled', false)) {
            return $this->error('wallet_unavailable', 'Wallets are currently unavailable.', 404);
        }

        try {
            $minorProfileId = $this->selectedMinorProfileId($request);

            if ($minorProfileId === null) {
                throw ValidationException::withMessages(['minor_profile_id' => 'Select a child account to view its wallet.']);
            }

            $this->minorProfiles->forGuardian($minorProfileId, (int) $request->customerId());

            $summary = $wallets->summary($minorProfileId);
            $wallet = $wallets->walletForMinorProfile($minorProfileId);
            $movements = $wallet->movements()->latest('id')->limit(50)->get();
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        }

        return response()->json([
            'wallet_id' => $summary->walletId,
            'minor_profile_id' => $summary->minorProfileId,
            'balance_baisa' => $summary->balanceBaisa,
            ...$this->walletBalances($wallet),
            'withdrawal_available_baisa' => $summary->availableBaisa,
            'currency' => $summary->currency,
            'status' => $summary->status,
            'withdrawals' => $wallet->withdrawalRequests()->latest('id')->limit(10)->get()->map(fn ($withdrawal): array => [
                'reference' => $withdrawal->reference,
                'amount_baisa' => $withdrawal->amount_baisa,
                'method' => $withdrawal->method,
                'status' => $withdrawal->status,
                'created_at' => $withdrawal->created_at?->toJSON(),
                'paid_at' => $withdrawal->paid_at?->toJSON(),
            ])->values()->all(),
            'movements' => $movements->map(fn ($movement): array => [
                'id' => $movement->id,
                'type' => $movement->type,
                'order_reference' => $movement->order_reference,
                'credit_baisa' => $movement->credit_baisa,
                'debit_baisa' => $movement->debit_baisa,
                'balance_after_baisa' => $movement->balance_after_baisa,
                'created_at' => $movement->created_at?->toJSON(),
            ])->values()->all(),
        ]);
    }

    public function walletTopUp(UchatWalletTopUpRequest $request, InitiateWalletTopUp $initiateTopUp): JsonResponse
    {
        if (! config('byruhaa.wallets.enabled', false)) {
            return $this->error('wallet_unavailable', 'Wallets are currently unavailable.', 404);
        }

        try {
            $minorProfileId = $this->selectedMinorProfileId($request);

            if ($minorProfileId === null) {
                throw ValidationException::withMessages(['minor_profile_id' => 'Select a child account before adding wallet funds.']);
            }

            $this->minorProfiles->forGuardian($minorProfileId, (int) $request->customerId());

            $operationKey = $request->string('idempotency_key')->toString();
            $amountBaisa = MoneyFactory::omrStringToBaisa((string) $request->validated('amount_omr'));
            $checkout = $initiateTopUp->execute(
                $minorProfileId,
                $operationKey,
                $amountBaisa,
                URL::temporarySignedRoute('customer.minor-profiles.wallet.top-up.success', now()->addHours(12), [
                    'minorProfile' => $minorProfileId,
                    'operationKey' => $operationKey,
                ]),
                URL::temporarySignedRoute('customer.minor-profiles.wallet.top-up.cancel', now()->addHours(12), [
                    'minorProfile' => $minorProfileId,
                    'operationKey' => $operationKey,
                ]),
            );
            $topUp = WalletTopUp::query()->where('operation_key', $operationKey)->firstOrFail();
        } catch (ValidationException $exception) {
            return $this->validationError($exception);
        } catch (PaymentGatewayException|RuntimeException) {
            return $this->error('payment_unavailable', 'The payment provider is temporarily unavailable.', 503);
        }

        return response()->json([
            'top_up_reference' => $topUp->reference,
            'payment_reference' => $checkout->paymentReference,
            'amount_baisa' => $checkout->amountBaisa,
            'currency' => $checkout->currency,
            'status' => $checkout->status,
            'checkout_url' => $checkout->checkoutUrl,
            'expires_at' => $checkout->expiresAt?->format(DATE_ATOM),
        ], 201);
    }

    public function walletMovements(UchatIdentityRequest $request, WalletService $wallets): JsonResponse
    {
        $wallet = $this->readableWallet($request, $wallets);
        $movements = $wallet->movements()->latest('id')->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => $movements->map(fn ($movement): array => [
                'id' => $movement->id,
                'type' => $movement->type,
                'order_reference' => $movement->order_reference,
                'credit_baisa' => $movement->credit_baisa,
                'debit_baisa' => $movement->debit_baisa,
                'balance_after_baisa' => $movement->balance_after_baisa,
                'created_at' => $movement->created_at?->toJSON(),
            ]),
            'meta' => ['current_page' => $movements->currentPage(), 'last_page' => $movements->lastPage(), 'per_page' => $movements->perPage(), 'total' => $movements->total()],
        ]);
    }

    public function walletTopUpStatus(string $reference, UchatIdentityRequest $request, WalletService $wallets): JsonResponse
    {
        $wallet = $this->readableWallet($request, $wallets);
        $topUp = $wallet->topUps()->where('reference', $reference)->with('payment')->first();
        if (! $topUp instanceof WalletTopUp) {
            return $this->error('top_up_not_found', 'The wallet top-up was not found.', 404);
        }

        return response()->json(['data' => [
            'top_up_reference' => $topUp->reference,
            'minor_profile_id' => $wallet->minor_profile_id,
            'status' => $topUp->status,
            'payment_status' => $topUp->payment?->state?->value,
            'payment_reference' => $topUp->payment?->reference,
            'amount_baisa' => $topUp->amount_baisa,
            'currency' => $topUp->currency,
            'credited_at' => $topUp->credited_at?->toJSON(),
            'reserved_refund_baisa' => $topUp->reserved_refund_baisa,
            'reserved_withdrawal_baisa' => $topUp->reserved_withdrawal_baisa,
            'eligible_refund_baisa' => 0,
            'refund_deadline_at' => null,
        ]]);
    }

    private function readableWallet(UchatIdentityRequest $request, WalletService $wallets): Wallet
    {
        if (! config('byruhaa.wallets.enabled', false)) {
            abort(response()->json(['code' => 'wallet_unavailable', 'message' => 'Wallets are currently unavailable.'], 404));
        }
        $profileId = $this->selectedMinorProfileId($request);
        if ($profileId === null) {
            throw ValidationException::withMessages(['minor_profile_id' => 'Select a child account to view its wallet.']);
        }
        $this->minorProfiles->forGuardian($profileId, (int) $request->customerId());

        return $wallets->walletForMinorProfile($profileId);
    }

    /** @return array{available_balance_baisa: int, reserved_balance_baisa: int, eligible_refund_baisa: int} */
    private function walletBalances(Wallet $wallet): array
    {
        $topUps = $wallet->topUps()->get();
        $spendable = (int) $topUps->whereIn('status', ['credited', 'refunding'])->sum(fn (WalletTopUp $topUp): int => max(0, $topUp->spendable_baisa - $topUp->reserved_refund_baisa - $topUp->reserved_withdrawal_baisa));

        return [
            'available_balance_baisa' => $wallet->status === 'active' ? max(0, min($wallet->balance_baisa, $spendable)) : 0,
            'reserved_balance_baisa' => (int) $topUps->sum(fn (WalletTopUp $topUp): int => $topUp->reserved_refund_baisa + $topUp->reserved_withdrawal_baisa),
            'eligible_refund_baisa' => 0,
        ];
    }

    private function selectedMinorProfileId(UchatRequest $request): ?int
    {
        $profileId = $request->minorProfileId();

        if ($profileId !== null) {
            $customerId = $request->customerId();

            if ($customerId === null) {
                throw ValidationException::withMessages(['minor_profile_id' => 'A guardian account is required for a minor profile.']);
            }

            $this->minorProfiles->forGuardian($profileId, $customerId);
        }

        return $profileId;
    }

    private function validationError(ValidationException $exception, int $status = 422, string $code = 'validation_failed'): JsonResponse
    {
        return $this->error($code, 'The request could not be completed.', $status, $exception->errors());
    }

    /** @param array<string, mixed> $errors */
    private function error(string $code, string $message, int $status, array $errors = []): JsonResponse
    {
        return response()->json(array_filter(['code' => $code, 'message' => $message, 'errors' => $errors], fn ($value): bool => $value !== []), $status);
    }
}
