<?php

namespace App\Http\Middleware;

use App\Enums\AffiliateStatus;
use App\Modules\Affiliates\Models\Affiliate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAffiliateIsApproved
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $affiliate = $request->user('affiliate');

        if (! $affiliate instanceof Affiliate) {
            return redirect()->route('affiliate.login');
        }

        if ($affiliate->status === AffiliateStatus::Pending) {
            return redirect()->route('affiliate.pending');
        }

        abort_unless($affiliate->status === AffiliateStatus::Approved, 403);

        return $next($request);
    }
}
