<?php

namespace App\Http\Controllers;

use App\Actions\HandleThawaniWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThawaniPaymentWebhookController extends Controller
{
    public function __invoke(Request $request, HandleThawaniWebhook $handleThawaniWebhook): JsonResponse
    {
        if (! $this->hasValidToken($request)) {
            return response()->json([
                'message' => 'Forbidden.',
            ], 403);
        }

        $event = $handleThawaniWebhook->handle(
            $request->all(),
            $request->headers->all(),
        );

        return response()->json([
            'status' => $event->status->value,
            'payment_id' => $event->payment_id,
        ], $event->response_status ?? 202);
    }

    private function hasValidToken(Request $request): bool
    {
        if (! app()->isProduction() && config('app.env') !== 'production') {
            return true;
        }

        $token = config('thawani.webhook.token');

        if (! is_scalar($token) || trim((string) $token) === '') {
            return false;
        }

        $providedToken = $request->query('token');

        return is_string($providedToken) && hash_equals((string) $token, $providedToken);
    }
}
