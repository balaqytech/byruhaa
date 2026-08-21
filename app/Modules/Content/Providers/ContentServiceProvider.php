<?php

namespace App\Modules\Content\Providers;

use App\Modules\Content\Models\PublicPage;
use App\Modules\Content\Policies\PublicPagePolicy;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;

class ContentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(PublicPage::class, PublicPagePolicy::class);

        ViewFacade::composer('layouts.public', function (View $view): void {
            $view->with([
                'publishedPolicyPages' => PublicPage::query()
                    ->published()
                    ->whereIn('key', PublicPage::FIXED_KEYS)
                    ->orderBy('id')
                    ->get(['key', 'title']),
                'siteIdentity' => app(GeneralSettings::class),
            ]);
        });
    }
}
