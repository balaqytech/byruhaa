<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Modules\Identity\Models\User;
use Tapp\FilamentAuditing\Models\Audit;

class AuditPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'ViewAny:Audit');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Audit $audit): bool
    {
        return $this->allows($user, 'View:Audit');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Audit $audit): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Audit $audit): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Audit $audit): bool
    {
        return $this->allows($user, 'Restore:Audit');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Audit $audit): bool
    {
        return false;
    }

    private function allows(User $user, string $permission): bool
    {
        return $user->role === UserRole::Admin
            || $user->can($permission);
    }
}
