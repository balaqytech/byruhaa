<?php

use App\Modules\Affiliates\Providers\AffiliatesServiceProvider;
use App\Modules\Content\Providers\ContentServiceProvider;
use App\Modules\Events\Providers\EventsServiceProvider;
use App\Modules\Finance\Providers\FinanceServiceProvider;
use App\Modules\Identity\Providers\IdentityServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    IdentityServiceProvider::class,
    EventsServiceProvider::class,
    FinanceServiceProvider::class,
    AffiliatesServiceProvider::class,
    ContentServiceProvider::class,
];
