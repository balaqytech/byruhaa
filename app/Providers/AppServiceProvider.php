<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Listeners\UpdateWebhookDeliveryStatus;
use App\Modules\Identity\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\WebhookServer\Events\FinalWebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallFailedEvent;
use Spatie\WebhookServer\Events\WebhookCallSucceededEvent;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerWebhookDeliveryListeners();
        $this->registerLogViewerAuthorization();
        $this->configureDefaults();
    }

    protected function registerWebhookDeliveryListeners(): void
    {
        Event::listen(WebhookCallSucceededEvent::class, UpdateWebhookDeliveryStatus::class);
        Event::listen(WebhookCallFailedEvent::class, UpdateWebhookDeliveryStatus::class);
        Event::listen(FinalWebhookCallFailedEvent::class, UpdateWebhookDeliveryStatus::class);
    }

    protected function registerLogViewerAuthorization(): void
    {
        Gate::define('viewLogViewer', static function (?User $user): bool {
            return $user?->role === UserRole::Admin
                || $user?->hasRole('super_admin')
                || $user?->can('View:LogViewer');
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
