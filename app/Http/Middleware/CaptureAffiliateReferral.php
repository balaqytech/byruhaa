<?php

namespace App\Http\Middleware;

use App\Enums\AffiliateStatus;
use App\Models\Affiliate;
use App\Services\AffiliateAttribution;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureAffiliateReferral
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $referralCode = $request->query('ref');

        if (is_string($referralCode) && filled($referralCode)) {
            $affiliate = Affiliate::query()
                ->where('code', $referralCode)
                ->where('status', AffiliateStatus::Approved->value)
                ->first();

            if ($affiliate instanceof Affiliate) {
                app(AffiliateAttribution::class)->store($affiliate, $request);
            }
        } else {
            app(AffiliateAttribution::class)->hydrateFromCookie($request);
        }

        return $next($request);
    }
}
