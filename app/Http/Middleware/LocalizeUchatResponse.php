<?php

namespace App\Http\Middleware;

use App\Support\UchatErrors;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
                $conflict = isset($exception->errors()['idempotency_key']) || isset($exception->errors()['operation_key']);
                $response = new JsonResponse(['code' => $conflict ? 'idempotency_conflict' : 'validation_failed', 'message' => $conflict ? __('api_uchat.idempotency_conflict') : 'The request data is invalid.', 'errors' => $exception->errors()], $conflict ? 409 : 422);
            } catch (HttpResponseException $exception) {
                $response = $exception->getResponse();
            } catch (ModelNotFoundException) {
                $response = new JsonResponse(['code' => 'resource_not_found', 'message' => __('api_uchat.resource_not_found')], 404);
            } catch (HttpExceptionInterface $exception) {
                $response = new JsonResponse(['code' => 'http_'.$exception->getStatusCode(), 'message' => $exception->getStatusCode() === 404 ? __('api_uchat.resource_not_found') : $exception->getMessage()], $exception->getStatusCode(), $exception->getHeaders());
            }
            if ($response instanceof JsonResponse && $response->getStatusCode() >= 400) {
                $data = $response->getData(true);
                if ($response->getStatusCode() === 422 && (isset($data['errors']['idempotency_key']) || isset($data['errors']['operation_key'])) && ($data['message'] ?? '') === 'The request could not be completed.') {
                    $response->setStatusCode(409);
                    $data['code'] = 'idempotency_conflict';
                    $data['message'] = __('api_uchat.idempotency_conflict');
                }
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
