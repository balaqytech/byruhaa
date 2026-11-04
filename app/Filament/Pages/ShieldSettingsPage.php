<?php

namespace App\Filament\Pages;

use App\Modules\Identity\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\SettingsPage;

abstract class ShieldSettingsPage extends SettingsPage
{
    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User
            && ($user->isPanelAdministrator() || $user->can('View:'.class_basename(static::class)));
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess() && parent::shouldRegisterNavigation();
    }
}
