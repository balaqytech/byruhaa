<?php

namespace App\Modules\Affiliates\Providers;

use App\Modules\Affiliates\Models\AffiliateCommission;
use App\Modules\Affiliates\Models\AffiliatePayoutRequest;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AffiliatesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Relation::morphMap([
            'App\\Models\\AffiliateCommission' => AffiliateCommission::class,
            'App\\Models\\AffiliatePayoutRequest' => AffiliatePayoutRequest::class,
        ]);
    }
}
