<?php

namespace App\Http\Middleware;

use App\Support\UchatErrors;
use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class LocalizeUchatResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $previousLocale = app()->getLocale();
        app()->setLocale($request->getPreferredLanguage(['ar', 'en']) ?? $previousLocale);
        try {
            try {
                $response = $next($request);
            } catch (ValidationException $exception) {
                $response = new JsonResponse(['code' => 'validation_failed', 'message' => 'The request data is invalid.', 'errors' => $exception->errors()], 422);
            } catch (HttpResponseException $exception) {
                $response = $exception->getResponse();
            }
            if ($response instanceof JsonResponse && $response->getStatusCode() >= 400) {
                $data = $response->getData(true);
                if (isset($data['errors']) && ! isset($data['code'])) {
                    $data['code'] = 'validation_failed';
                    $data['message'] = 'The request data is invalid.';
                }
                $reasons = [];
                foreach ($data['errors'] ?? [] as $field => $messages) {
                    foreach ($messages as $index => $message) {
                        if (isset(UchatErrors::REASONS[$message])) {
                            $reasons[$field][] = UchatErrors::REASONS[$message];
                        }
                        $data['errors'][$field][$index] = UchatErrors::translate($message);
                    }
                }
                if ($reasons !== []) {
                    $data['reasons'] = $reasons;
                }
                if (isset($data['message'])) {
                    $data['message'] = UchatErrors::translate($data['message']);
                }
                $response->setData($data);
            }

            return $response;
        } finally {
            app()->setLocale($previousLocale);
        }
    }
}
