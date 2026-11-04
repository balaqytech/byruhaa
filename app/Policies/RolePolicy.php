<?php

declare(strict_types=1);

namespace App\Policies;

use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ViewAny:Role');
    }

    public function view(AuthUser $authUser, Role $role): bool
    {
        return $this->allows($authUser, 'View:Role');
    }

    public function create(AuthUser $authUser): bool
    {
        return $this->isAdministrator($authUser);
    }

    public function update(AuthUser $authUser, Role $role): bool
    {
        return $this->isAdministrator($authUser) && $role->name !== config('filament-shield.super_admin.name');
    }

    public function delete(AuthUser $authUser, Role $role): bool
    {
        return $this->isAdministrator($authUser) && $role->name !== config('filament-shield.super_admin.name');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return false;
    }

    public function restore(AuthUser $authUser, Role $role): bool
    {
        return false;
    }

    public function forceDelete(AuthUser $authUser, Role $role): bool
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

    public function replicate(AuthUser $authUser, Role $role): bool
    {
        return false;
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:Role');
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
