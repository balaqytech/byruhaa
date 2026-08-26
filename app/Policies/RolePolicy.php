<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Modules\Identity\Models\Role;
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
        return $this->allows($authUser, 'Create:Role');
    }

    public function update(AuthUser $authUser, Role $role): bool
    {
        return $this->allows($authUser, 'Update:Role');
    }

    public function delete(AuthUser $authUser, Role $role): bool
    {
        return $this->allows($authUser, 'Delete:Role');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:Role');
    }

    public function restore(AuthUser $authUser, Role $role): bool
    {
        return $this->allows($authUser, 'Restore:Role');
    }

    public function forceDelete(AuthUser $authUser, Role $role): bool
    {
        return $this->allows($authUser, 'ForceDelete:Role');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:Role');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:Role');
    }

    public function replicate(AuthUser $authUser, Role $role): bool
    {
        return $this->allows($authUser, 'Replicate:Role');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:Role');
    }

    private function allows(AuthUser $authUser, string $permission): bool
    {
        return $authUser->getAttribute('role') === UserRole::Admin
            || $authUser->can($permission);
    }
}
