<?php

namespace App\Modules\Store\Http\Controllers;

use App\Exceptions\PaymentGatewayException;
use App\Modules\Finance\Contracts\WalletService;
use App\Modules\Identity\Models\MinorProfile;
use App\Modules\Store\Actions\ConfirmWalletOrder;
use App\Modules\Store\Actions\InitiateStorePayment;
use App\Modules\Store\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MinorProfileStoreController
{
    public function dashboard(): RedirectResponse
    {
        return redirect()->route('minor.orders.index');
    }

    public function orders(WalletService $wallets): View
    {
        $profileId = (int) Auth::guard('minor-profile')->id();
        $profile = Auth::guard('minor-profile')->user();
        $wallet = config('byruhaa.wallets.enabled', false) ? $wallets->walletForMinorProfile($profileId) : null;
        $wallet?->load(['movements' => fn ($query) => $query->latest('id')->limit(10)]);
        $reservedBalance = $wallet ? (int) $wallet->topUps()->sum('reserved_refund_baisa') : 0;

        return view('pages.minor.store.orders.index', [
            'profile' => $profile->loadMissing('familyMember.customer'),
            'wallet' => $wallet,
            'reservedBalance' => $reservedBalance,
            'orders' => Order::query()->where('minor_profile_id', $profileId)->latest('created_at')->latest('id')->paginate(10),
            'notifications' => $profile->notifications()->latest()->limit(10)->get(),
        ]);
    }

    public function show(Order $order): View
    {
        abort_unless($this->owns($order), 404);

        return view('pages.minor.store.orders.show', [
            'order' => $order->load(['items', 'statusHistory']),
            'paymentBlockReason' => $this->paymentBlockReason($order, Auth::guard('minor-profile')->user()),
        ]);
    }

    public function pay(Request $request, Order $order, InitiateStorePayment $initiatePayment, ConfirmWalletOrder $confirmWalletOrder): RedirectResponse
    {
        abort_unless($this->owns($order), 404);

        $profile = Auth::guard('minor-profile')->user();
        $profile->loadMissing('familyMember');

        $paymentBlockReason = $this->paymentBlockReason($order, $profile);
        if ($paymentBlockReason !== null) {
            return back()->withErrors(['payment' => $paymentBlockReason]);
        }

        try {
            if ($order->payment_method === 'wallet') {
                $confirmWalletOrder->execute($order, (int) $profile->familyMember->customer_id, (int) $profile->id);

                return redirect()->route('minor.orders.show', $order->payment_token);
            }

            $payment = $initiatePayment->execute($order, (int) $profile->familyMember->customer_id, (int) $profile->id);
        } catch (ValidationException|PaymentGatewayException|RuntimeException $exception) {
            if (! $exception instanceof ValidationException) {
                report($exception);
            }

            return back()->withErrors($exception instanceof ValidationException ? $exception->errors() : ['payment' => 'تعذر بدء الدفع الآن.']);
        }

        return redirect()->away($payment->checkoutUrl);
    }

    private function paymentBlockReason(Order $order, MinorProfile $profile): ?string
    {
        if ($order->payment_method !== 'wallet') {
            return $profile->direct_payment_enabled
                ? null
                : 'هذا الطلب للدفع عبر ثواني. يلزم أن يدفع وليّ الأمر أو يفعّل «السماح بالدفع المباشر» من حسابه. السماح بالدفع من المحفظة صلاحية مختلفة.';
        }

        if (! config('byruhaa.wallets.enabled', false)) {
            return 'الدفع بالمحفظة غير متاح حاليًا.';
        }

        if (! $profile->wallet_spending_enabled) {
            return 'يلزم أن يفعّل وليّ الأمر «السماح بالدفع من المحفظة» من حسابه.';
        }

        if ($profile->guardian()->requiresPhoneVerification()) {
            return 'يلزم توثيق هاتف وليّ الأمر قبل الدفع من المحفظة.';
        }

        return null;
    }

    private function owns(Order $order): bool
    {
        return (int) $order->getRawOriginal('minor_profile_id') === (int) Auth::guard('minor-profile')->id();
    }
}
