<?php

namespace App\Modules\Content\Providers;

use App\Modules\Content\Models\PublicPage;
use App\Modules\Content\Policies\PublicPagePolicy;
use App\Settings\GeneralSettings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;

class ContentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(PublicPage::class, PublicPagePolicy::class);

        ViewFacade::composer(['layouts.public', 'components.policy-links'], function (View $view): void {
            $view->with([
                'publishedPolicyPages' => $this->publishedPolicyPages(),
                'siteIdentity' => app(GeneralSettings::class),
            ]);
        });
    }

    /** @return Collection<int, PublicPage> */
    private function publishedPolicyPages(): Collection
    {
        return once(fn (): Collection => PublicPage::query()
            ->published()
            ->whereIn('key', PublicPage::FIXED_KEYS)
            ->orderBy('id')
            ->get(['key', 'title']));
    }
}
