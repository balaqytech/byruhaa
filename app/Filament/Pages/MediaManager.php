<?php

namespace App\Filament\Pages;

use App\Modules\Identity\Models\User;
use Filament\Facades\Filament;
use Slimani\MediaManager\Pages\MediaManager as BaseMediaManager;

class MediaManager extends BaseMediaManager
{
    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User
            && ($user->isPanelAdministrator() || $user->can('View:MediaManager'));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess() && parent::shouldRegisterNavigation();
    }
}
