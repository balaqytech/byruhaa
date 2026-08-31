<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMinorProfile
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $profile = Auth::guard('minor-profile')->user();

        if ($profile === null) {
            return redirect()->route('minor.login');
        }

        if (! $profile->isActive()) {
            Auth::guard('minor-profile')->logout();

            return redirect()->route('minor.login')->withErrors([
                'login' => 'هذا الحساب غير متاح حاليًا.',
            ]);
        }

        return $next($request);
    }
}
