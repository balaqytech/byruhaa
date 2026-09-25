<?php

namespace App\Modules\Store\Data;

use App\Modules\Store\Enums\PricingChannel;
use App\Modules\Store\Enums\PricingTier;
use Carbon\CarbonImmutable;

final readonly class PricingContext
{
    /** @param list<string> $couponCodes */
    public function __construct(
        public PricingTier $tier,
        public PricingChannel $channel,
        public ?int $customerId,
        public ?int $minorProfileId,
        public CarbonImmutable $evaluatedAt,
        public array $couponCodes = [],
    ) {}

    public function isMember(): bool
    {
        return $this->tier === PricingTier::Member;
    }
}
