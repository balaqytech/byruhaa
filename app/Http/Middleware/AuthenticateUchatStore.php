<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateUchatStore
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $configured = config('byruhaa.uchat.api_token');
        $provided = $request->bearerToken();

        $ownerSecret = config('byruhaa.uchat.owner_key_secret');

        if (! is_string($configured) || trim($configured) === '' || ! is_string($ownerSecret) || trim($ownerSecret) === '') {
            return new JsonResponse([
                'code' => 'uchat_not_configured',
                'message' => 'The UChat Store integration is not configured.',
            ], 503);
        }

        if (! is_string($provided) || $provided === '' || ! hash_equals($configured, $provided)) {
            return new JsonResponse([
                'code' => 'uchat_unauthorized',
                'message' => 'The UChat credentials are invalid or not configured.',
            ], 401);
        }

        return $next($request);
    }
}
