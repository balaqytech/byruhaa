<?php

namespace App\Modules\Store\Http\Controllers;

use App\Exceptions\PaymentGatewayException;
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

    public function orders(): View
    {
        $profileId = (int) Auth::guard('minor-profile')->id();
        $profile = Auth::guard('minor-profile')->user();

        return view('pages.minor.store.orders.index', [
            'orders' => Order::query()->where('minor_profile_id', $profileId)->latest('created_at')->latest('id')->get(),
            'notifications' => $profile->notifications()->latest()->limit(10)->get(),
        ]);
    }

    public function show(Order $order): View
    {
        abort_unless($this->owns($order), 404);

        return view('pages.minor.store.orders.show', [
            'order' => $order->load(['items', 'statusHistory']),
        ]);
    }

    public function pay(Request $request, Order $order, InitiateStorePayment $initiatePayment): RedirectResponse
    {
        abort_unless($this->owns($order), 404);

        $profile = Auth::guard('minor-profile')->user();
        $profile->loadMissing('familyMember');

        try {
            $payment = $initiatePayment->execute($order, (int) $profile->familyMember->customer_id, (int) $profile->id);
        } catch (ValidationException|PaymentGatewayException|RuntimeException $exception) {
            if (! $exception instanceof ValidationException) {
                report($exception);
            }

            return back()->withErrors($exception instanceof ValidationException ? $exception->errors() : ['payment' => 'تعذر بدء الدفع الآن.']);
        }

        return redirect()->away($payment->checkoutUrl);
    }

    private function owns(Order $order): bool
    {
        return (int) $order->getRawOriginal('minor_profile_id') === (int) Auth::guard('minor-profile')->id();
    }
}
