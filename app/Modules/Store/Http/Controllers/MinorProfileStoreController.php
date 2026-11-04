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
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MinorProfileStoreController
{
    public function dashboard(WalletService $wallets): View
    {
        $profileId = (int) Auth::guard('minor-profile')->id();
        /** @var MinorProfile $profile */
        $profile = Auth::guard('minor-profile')->user();
        $wallet = config('byruhaa.wallets.enabled', false) ? $wallets->walletForMinorProfile($profileId) : null;
        $wallet?->load(['movements' => fn ($query) => $query->latest('id')->limit(5)]);
        $activeStatuses = ['pending_payment', 'confirmed', 'accepted', 'preparing', 'ready_for_pickup', 'refund_pending'];
        $orders = Order::query()->where('minor_profile_id', $profileId);

        return view('pages.minor.dashboard', [
            'profile' => $profile->loadMissing('familyMember.customer'),
            'wallet' => $wallet,
            'reservedBalance' => $wallet ? (int) $wallet->topUps()->sum('reserved_refund_baisa') : 0,
            'recentOrders' => (clone $orders)->latest('created_at')->latest('id')->limit(3)->get(),
            'latestActiveOrder' => (clone $orders)->whereIn('status', $activeStatuses)->latest('created_at')->latest('id')->first(),
            'ordersCount' => (clone $orders)->count(),
            'activeOrdersCount' => (clone $orders)->whereIn('status', $activeStatuses)->count(),
            'notifications' => $profile->notifications()->latest()->limit(3)->get(),
            'unreadNotificationsCount' => $profile->unreadNotifications()->count(),
            'browserNotifications' => $this->browserNotifications($profile),
        ]);
    }

    public function orders(): View
    {
        $profileId = (int) Auth::guard('minor-profile')->id();
        /** @var MinorProfile $profile */
        $profile = Auth::guard('minor-profile')->user();
        $orders = Order::query()->where('minor_profile_id', $profileId);

        return view('pages.minor.store.orders.index', [
            'profile' => $profile->loadMissing('familyMember'),
            'orders' => (clone $orders)->latest('created_at')->latest('id')->paginate(12),
            'ordersCount' => (clone $orders)->count(),
            'activeOrdersCount' => (clone $orders)->whereIn('status', ['pending_payment', 'confirmed', 'accepted', 'preparing', 'ready_for_pickup', 'refund_pending'])->count(),
            'completedOrdersCount' => (clone $orders)->where('status', 'completed')->count(),
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

    public function walletMovements(WalletService $wallets): View
    {
        abort_unless(config('byruhaa.wallets.enabled', false), 404);

        /** @var MinorProfile $profile */
        $profile = Auth::guard('minor-profile')->user();
        $wallet = $wallets->walletForMinorProfile((int) $profile->id);

        return view('pages.minor.wallet.movements', [
            'profile' => $profile->loadMissing('familyMember'),
            'wallet' => $wallet,
            'reservedBalance' => (int) $wallet->topUps()->sum('reserved_refund_baisa'),
            'movements' => $wallet->movements()->latest('id')->cursorPaginate(20),
        ]);
    }

    public function notifications(): View
    {
        /** @var MinorProfile $profile */
        $profile = Auth::guard('minor-profile')->user();

        return view('pages.minor.notifications.index', [
            'profile' => $profile->loadMissing('familyMember'),
            'notifications' => $profile->notifications()->latest()->paginate(20),
            'unreadCount' => $profile->unreadNotifications()->count(),
        ]);
    }

    public function markAllNotificationsAsRead(): RedirectResponse
    {
        /** @var MinorProfile $profile */
        $profile = Auth::guard('minor-profile')->user();
        $profile->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'تم تحديد جميع الإشعارات كمقروءة.');
    }

    public function openNotification(string $notification): RedirectResponse
    {
        /** @var MinorProfile $profile */
        $profile = Auth::guard('minor-profile')->user();
        $storedNotification = $profile->notifications()->findOrFail($notification);
        $storedNotification->markAsRead();

        $url = (string) data_get($storedNotification->data, 'url', route('minor.notifications.index'));

        return redirect()->to(Str::startsWith($url, url('/')) ? $url : route('minor.notifications.index'));
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

        return null;
    }

    private function owns(Order $order): bool
    {
        return (int) $order->getRawOriginal('minor_profile_id') === (int) Auth::guard('minor-profile')->id();
    }

    /** @return array{enabled: bool, has_guardian_consent: bool, vapid_public_key: string, store_url: string, destroy_url: string} */
    private function browserNotifications(MinorProfile $profile): array
    {
        return [
            'enabled' => config('byruhaa.minor_accounts.browser_notifications.enabled', true),
            'has_guardian_consent' => $profile->consents()->where('purpose', 'browser_notifications')->exists(),
            'vapid_public_key' => (string) config('webpush.vapid.public_key'),
            'store_url' => route('minor.push-subscriptions.store'),
            'destroy_url' => route('minor.push-subscriptions.destroy'),
        ];
    }
}
