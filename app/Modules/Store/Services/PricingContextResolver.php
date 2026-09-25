<?php

namespace App\Modules\Store\Services;

use App\Modules\Store\Data\PricingContext;
use App\Modules\Store\Enums\PricingChannel;
use App\Modules\Store\Enums\PricingTier;
use App\Modules\Store\Models\Cart;
use Carbon\CarbonImmutable;

class PricingContextResolver
{
    public function forCart(Cart $cart, PricingChannel $channel = PricingChannel::Storefront): PricingContext
    {
        $customerId = $cart->getRawOriginal('customer_id');
        $minorProfileId = $cart->getRawOriginal('minor_profile_id');

        return $this->forIdentity(
            is_numeric($customerId) ? (int) $customerId : null,
            is_numeric($minorProfileId) ? (int) $minorProfileId : null,
            $channel,
        );
    }

    /** @param list<string> $couponCodes */
    public function forIdentity(
        ?int $customerId,
        ?int $minorProfileId = null,
        PricingChannel $channel = PricingChannel::Storefront,
        array $couponCodes = [],
    ): PricingContext {
        return new PricingContext(
            tier: $customerId === null ? PricingTier::Standard : PricingTier::Member,
            channel: $channel,
            customerId: $customerId,
            minorProfileId: $minorProfileId,
            evaluatedAt: CarbonImmutable::now(),
            couponCodes: array_values(array_unique($couponCodes)),
        );
    }
}
