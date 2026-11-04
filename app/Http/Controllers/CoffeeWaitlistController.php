<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCoffeeWaitlistRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class CoffeeWaitlistController extends Controller
{
    public function __invoke(StoreCoffeeWaitlistRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $payload = [
            'role' => match ($validated['role']) {
                'parent' => 'وليّ أمر',
                'teacher' => 'معلّم',
                'principal' => 'إدارة مدرسة',
                'directorate' => 'مديرية التعليم',
            },
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'wilayat' => $validated['wilayat'],
            'sons_count' => $validated['role'] === 'parent' ? $validated['sons_count'] : null,
            'school' => $validated['role'] === 'parent' ? null : $validated['school'],
            'source' => 'website',
            'utm_source' => $validated['utm_source'] ?? null,
            'utm_medium' => $validated['utm_medium'] ?? null,
            'utm_campaign' => $validated['utm_campaign'] ?? null,
        ];

        try {
            $response = Http::connectTimeout(5)
                ->timeout(10)
                ->post(config('coffee.waitlist_webhook_url'), $payload);
        } catch (ConnectionException $exception) {
            report($exception);

            return response()->json(['message' => 'تعذّر التسجيل الآن. حاول مرة أخرى.'], 502);
        }

        if ($response->failed()) {
            report(new \RuntimeException('Coffee waitlist webhook returned HTTP '.$response->status()));

            return response()->json(['message' => 'تعذّر التسجيل الآن. حاول مرة أخرى.'], 502);
        }

        return response()->json(['message' => 'تم التسجيل بنجاح.']);
    }
}
