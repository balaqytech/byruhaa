<?php

namespace App\Http\Middleware;

use App\Settings\GeneralSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShowComingSoonPage
{
    public function __construct(private GeneralSettings $settings) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->settings->coming_soon_enabled || $this->shouldRemainAvailable($request)) {
            return $next($request);
        }

        return response()
            ->view('coming-soon', status: Response::HTTP_SERVICE_UNAVAILABLE)
            ->header('Retry-After', '3600');
    }

    private function shouldRemainAvailable(Request $request): bool
    {
        return $request->is('admin', 'admin/*', 'livewire-*');
    }
}
