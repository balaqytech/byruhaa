<?php

namespace App\Modules\Events\Providers;

use App\Modules\Events\Models\Booking;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class EventsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap Events services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'App\\Models\\Booking' => Booking::class,
        ]);
    }
}
