<?php

use App\Http\Middleware\ShowComingSoonPage;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', ShowComingSoonPage::class);
        $middleware->redirectGuestsTo(fn (Request $request): string => match (true) {
            $request->is('cashier', 'cashier/*') => route('cashier.login'),
            $request->is('barista', 'barista/*') => route('barista.login'),
            $request->is('pickup', 'pickup/*') => route('pickup.login'),
            default => route('login'),
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request): ?JsonResponse {
            if (! $request->is('api/v1/integrations/uchat', 'api/v1/integrations/uchat/*')) {
                return null;
            }
            $status = $exception->getStatusCode();
            $code = match ($status) {
                404 => 'resource_not_found',
                405 => 'method_not_allowed',
                429 => 'rate_limited',
                default => 'http_'.$status,
            };

            return response()->json(['code' => $code, 'message' => __($status === 404 ? 'api_uchat.resource_not_found' : 'api_uchat.request_failed')], $status, $exception->getHeaders());
        });
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
