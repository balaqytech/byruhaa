<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class IdempotencyConflict extends RuntimeException
{
    public function render(): JsonResponse
    {
        return response()->json(['code' => 'idempotency_conflict', 'message' => __('api_uchat.idempotency_conflict')], 409);
    }
}
