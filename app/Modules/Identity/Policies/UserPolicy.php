<?php

namespace App\Modules\Identity\Policies;

use App\Enums\UserRole;
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
        return $this->allows($authUser, 'Create:User');
    }

    public function update(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Update:User');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Delete:User');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'DeleteAny:User');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Restore:User');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDelete:User');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'ForceDeleteAny:User');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'RestoreAny:User');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Replicate:User');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $this->allows($authUser, 'Reorder:User');
    }

    private function allows(AuthUser $authUser, string $permission): bool
    {
        return $authUser->getAttribute('role') === UserRole::Admin
            || $authUser->can($permission);
    }
}
