<?php

namespace App\Filament\Resources;

use App\Modules\Identity\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use UnitEnum;

abstract class ShieldResource extends Resource
{
    public static function getAuthorizationResponse(string|UnitEnum $action, ?Model $record = null): Response
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return Response::deny();
        }

        $ability = match (true) {
            $action instanceof BackedEnum => $action->value,
            $action instanceof UnitEnum => $action->name,
            default => $action,
        };

        $permission = Str::studly($ability).':'.class_basename(static::getModel());

        return $user->isPanelAdministrator() || $user->can($permission)
            ? Response::allow()
            : Response::deny();
    }
}
