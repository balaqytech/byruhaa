<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Modules\Finance\Actions\InitiateWalletTopUp;
use App\Modules\Finance\Contracts\PaymentService;
use App\Modules\Finance\Contracts\WalletService;
use App\Modules\Finance\Models\Payment;
use App\Modules\Finance\Models\WalletTopUp;
use App\Modules\Identity\Models\MinorProfile;
use App\Support\Money\MoneyFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WalletController
{
    public function show(Request $request, MinorProfile $minorProfile, WalletService $wallets): View
    {
        $this->assertOwnedBy($request, $minorProfile);
        abort_unless(config('byruhaa.wallets.enabled', false), 404);

        $wallet = $wallets->walletForMinorProfile($minorProfile->id);

        return view('pages.customer.minor-profiles.wallet', [
            'minorProfile' => $minorProfile->load('familyMember'),
            'wallet' => $wallet->load(['movements' => fn ($query) => $query->latest('id')->limit(50)]),
        ]);
    }

    public function topUp(Request $request, MinorProfile $minorProfile, InitiateWalletTopUp $initiateTopUp): RedirectResponse
    {
        $this->assertOwnedBy($request, $minorProfile);
        abort_unless(config('byruhaa.wallets.enabled', false), 404);
        abort_unless($minorProfile->isActive(), 404);

        $validated = $request->validate([
            'amount_omr' => ['required', 'numeric', 'min:0.100', 'max:100000'],
        ]);

        try {
            $amountBaisa = MoneyFactory::omrStringToBaisa((string) $validated['amount_omr']);
            $operationKey = (string) ($request->input('operation_key') ?: Str::uuid());
            $checkout = $initiateTopUp->execute(
                $minorProfile->id,
                $operationKey,
                $amountBaisa,
                URL::temporarySignedRoute('customer.minor-profiles.wallet.top-up.success', now()->addHours(12), ['minorProfile' => $minorProfile->id, 'operationKey' => $operationKey]),
                URL::temporarySignedRoute('customer.minor-profiles.wallet.top-up.cancel', now()->addHours(12), ['minorProfile' => $minorProfile->id, 'operationKey' => $operationKey]),
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()->away($checkout->checkoutUrl);
    }

    public function success(Request $request, MinorProfile $minorProfile, string $operationKey, PaymentService $payments, WalletService $wallets): RedirectResponse
    {
        $this->assertOwnedBy($request, $minorProfile);
        $walletTopUp = $this->topUpFor($minorProfile, $operationKey);
        abort_unless($walletTopUp->wallet()->where('minor_profile_id', $minorProfile->id)->exists(), 404);

        $verification = $payments->verifySubject('wallet_topup', $walletTopUp->reference);
        if ($verification?->status === 'paid') {
            $payment = Payment::query()->where('reference', $verification->paymentReference)->first();
            if ($payment instanceof Payment) {
                $wallets->creditTopUp($payment);
            }
        }

        $status = $verification === null ? 'pending' : $verification->status;

        return redirect()->route('customer.minor-profiles.wallet', $minorProfile)->with('wallet_status', $status);
    }

    public function cancel(Request $request, MinorProfile $minorProfile, string $operationKey, PaymentService $payments): RedirectResponse
    {
        $this->assertOwnedBy($request, $minorProfile);
        $walletTopUp = $this->topUpFor($minorProfile, $operationKey);
        abort_unless($walletTopUp->wallet()->where('minor_profile_id', $minorProfile->id)->exists(), 404);

        if ($walletTopUp->payment_id !== null) {
            $payments->cancelPayment($walletTopUp->payment()->value('reference'));
        }

        return redirect()->route('customer.minor-profiles.wallet', $minorProfile)->with('wallet_status', 'cancelled');
    }

    private function assertOwnedBy(Request $request, MinorProfile $minorProfile): void
    {
        abort_unless((int) $minorProfile->familyMember()->value('customer_id') === (int) $request->user('customer')->getAuthIdentifier(), 404);
    }

    private function topUpFor(MinorProfile $minorProfile, string $operationKey): WalletTopUp
    {
        return WalletTopUp::query()
            ->where('operation_key', $operationKey)
            ->whereHas('wallet', fn ($query) => $query->where('minor_profile_id', $minorProfile->id))
            ->firstOrFail();
    }
}
