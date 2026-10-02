<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\TrackOrderRequest;
use App\Modules\Identity\Services\PhoneNumberNormalizer;
use App\Modules\Store\Actions\BuildOrderTrackingProgress;
use App\Modules\Store\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class TrackOrderController extends Controller
{
    public function show(): Response
    {
        return response()->view('pages.public.site.store.track-order', [
            'title' => 'تتبع طلبك | قهوة بيرحاء',
            'metaDescription' => 'تابع حالة طلب قهوة بيرحاء والخطوات المتبقية للاستلام.',
            'robots' => 'noindex,nofollow',
            'order' => null,
            'progress' => null,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function lookup(
        TrackOrderRequest $request,
        PhoneNumberNormalizer $phoneNumbers,
        BuildOrderTrackingProgress $buildProgress,
    ): Response|RedirectResponse {
        $data = $request->validated();
        $reference = Str::upper(trim($data['reference']));
        $order = Order::query()->where('reference', $reference)->first();
        $submittedPhone = $phoneNumbers->normalize($data['phone']);
        $storedPhone = $phoneNumbers->normalize($order?->customer_phone);

        if ($order === null || $submittedPhone === null || $storedPhone === null || ! hash_equals($storedPhone, $submittedPhone)) {
            return redirect()->route('store.orders.track')
                ->withErrors(['lookup' => 'تعذر العثور على طلب بهذه البيانات. تحقق من رقم الطلب والهاتف ثم حاول مجددًا.'])
                ->withInput(['reference' => $reference]);
        }

        $order->load('statusHistory');

        return response()->view('pages.public.site.store.track-order', [
            'title' => 'تتبع طلبك | قهوة بيرحاء',
            'metaDescription' => 'تابع حالة طلب قهوة بيرحاء والخطوات المتبقية للاستلام.',
            'robots' => 'noindex,nofollow',
            'order' => $order,
            'progress' => $buildProgress->execute($order),
        ])->header('Cache-Control', 'private, no-store');
    }
}
