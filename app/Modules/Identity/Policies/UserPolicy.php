<?php

namespace App\Modules\Identity\Policies;

use App\Modules\Identity\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:User');
    }

    public function view(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'View:User');
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->isAdministrator($authUser);
    }

    public function update(AuthUser $authUser, ?User $user = null): bool
    {
        return $this->isAdministrator($authUser);
    }

    public function delete(AuthUser $authUser, ?User $user = null): bool
    {
        return $this->isAdministrator($authUser)
            && $user !== null
            && $authUser->getKey() !== $user->getKey()
            && (! $user->isPanelAdministrator() || User::role(config('filament-shield.super_admin.name'))->count() > 1);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restore(AuthUser $authUser): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return false;
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function replicate(AuthUser $authUser): bool
    {
        return false;
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:User');
    }

    private function allows(AuthUser $authUser, string $permission): bool
    {
        return $this->isAdministrator($authUser)
            || $authUser->can($permission);
    }

    private function isAdministrator(AuthUser $authUser): bool
    {
        return $authUser instanceof User && $authUser->isPanelAdministrator();
    }
}
