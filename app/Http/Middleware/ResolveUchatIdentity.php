<?php

namespace App\Http\Middleware;

use App\Data\UchatIdentity;
use App\Modules\Identity\Contracts\CustomerIdentityResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveUchatIdentity
{
    public function __construct(private CustomerIdentityResolver $resolver) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $phone = $this->resolver->normalizePhone((string) $request->header('X-WhatsApp-Phone'));
        validator(['uchat_phone' => $phone], ['uchat_phone' => ['required', 'string', 'phone:INTERNATIONAL,OM']])->validate();
        validator($request->all(), ['customer_id' => ['prohibited'], 'uchat_phone' => ['prohibited'], 'phone_number' => ['prohibited']])->validate();
        $identity = new UchatIdentity($phone, $this->resolver->findCustomerIdByPhone($phone));
        $request->attributes->set(UchatIdentity::class, $identity);
        if ($request->filled('minor_profile_id')) {
            validator($request->all(), ['minor_profile_id' => ['integer', 'min:1']])->validate();
            abort_if($identity->customerId === null, response()->json(['code' => 'customer_account_required', 'message' => __('api_uchat.customer_account_required')], 409));
            abort_unless($this->resolver->ownsMinorProfile($identity->customerId, $request->integer('minor_profile_id')), 404);
        }

        return $next($request);
    }
}
