<?php

namespace App\Http\Controllers\Store;

use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Store\StorePaymentRequest;
use App\Modules\Finance\Contracts\PaymentService;
use App\Modules\Store\Actions\ConfirmStorePayment;
use App\Modules\Store\Actions\InitiateStorePayment;
use App\Modules\Store\Actions\ResolveStoreOrder;
use App\Modules\Store\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PaymentController extends Controller
{
    public function status(Request $request, Order $order): View
    {
        $customerId = $this->customerId($request);
        $ownerId = $order->getRawOriginal('customer_id');

        abort_if($customerId !== null && $ownerId !== null && (int) $ownerId !== $customerId, 404);

        return view('pages.public.site.store.order-status', [
            'order' => $order->load(['items', 'statusHistory']),
            'title' => 'حالة طلب قهوة بيرحاء',
            'metaDescription' => 'متابعة حالة طلب قهوة بيرحاء والدفع والاستلام.',
        ]);
    }

    public function store(StorePaymentRequest $request, Order $order, ResolveStoreOrder $resolveOrder, InitiateStorePayment $initiatePayment): RedirectResponse|JsonResponse
    {
        try {
            $customerId = $this->customerId($request);
            $order = $resolveOrder->execute((string) $order->payment_token, $customerId, $request->hasValidSignature());
            $payment = $initiatePayment->execute($order, $customerId, null, $request->hasValidSignature());
        } catch (ValidationException $exception) {
            if (! $request->expectsJson()) {
                return back()->withErrors($exception->errors());
            }

            return response()->json(['message' => $exception->getMessage(), 'errors' => $exception->errors()], 422);
        } catch (PaymentGatewayException|RuntimeException) {
            if (! $request->expectsJson()) {
                return back()->withErrors(['payment' => 'تعذر الاتصال ببوابة الدفع. حاول مرة أخرى.']);
            }

            return response()->json(['message' => 'The payment provider is temporarily unavailable.'], 503);
        }

        if (! $request->expectsJson()) {
            return redirect()->away($payment->checkoutUrl);
        }

        return response()->json([
            'payment_reference' => $payment->paymentReference,
            'status' => $payment->status,
            'checkout_url' => $payment->checkoutUrl,
            'amount_baisa' => $payment->amountBaisa,
            'currency' => $payment->currency,
            'session_id' => $payment->sessionId,
            'expires_at' => $payment->expiresAt?->format(DATE_ATOM),
        ], 201);
    }

    public function success(Request $request, Order $order, PaymentService $payments, ConfirmStorePayment $confirmPayment): RedirectResponse|JsonResponse
    {
        try {
            $verification = $payments->verifySubject('store_order', $order->reference);
            if ($verification?->status === 'paid') {
                $confirmPayment->execute($verification);
            }
        } catch (ValidationException $exception) {
            if (! $request->expectsJson()) {
                return redirect()->to($this->statusUrl($order));
            }

            return response()->json(['message' => $exception->getMessage(), 'errors' => $exception->errors()], 422);
        }

        if (! $request->expectsJson()) {
            return redirect()->to($this->statusUrl($order));
        }

        return response()->json([
            'order_reference' => $order->reference,
            'payment_reference' => $verification?->paymentReference,
            'status' => $verification ? $verification->status : 'pending',
        ]);
    }

    public function cancel(Request $request, Order $order, PaymentService $payments, ConfirmStorePayment $confirmPayment): RedirectResponse|JsonResponse
    {
        try {
            $verification = $payments->verifySubject('store_order', $order->reference);
            if ($verification?->status === 'paid') {
                $confirmPayment->execute($verification);
            }
            if ($verification?->status === 'pending') {
                $verification = $payments->cancelPayment($verification->paymentReference);
            }
        } catch (ValidationException $exception) {
            if (! $request->expectsJson()) {
                return redirect()->to($this->statusUrl($order));
            }

            return response()->json(['message' => $exception->getMessage(), 'errors' => $exception->errors()], 422);
        }

        if (! $request->expectsJson()) {
            return redirect()->to($this->statusUrl($order));
        }

        return response()->json([
            'order_reference' => $order->reference,
            'payment_reference' => $verification?->paymentReference,
            'status' => $verification ? $verification->status : 'pending',
        ]);
    }

    private function customerId(Request $request): ?int
    {
        $identifier = $request->user('customer')?->getAuthIdentifier();

        return is_numeric($identifier) ? (int) $identifier : null;
    }

    private function statusUrl(Order $order): string
    {
        return URL::temporarySignedRoute(
            'store.orders.status',
            now()->addHours(12),
            ['order' => $order->payment_token],
        );
    }
}
