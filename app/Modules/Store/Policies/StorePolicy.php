<?php

namespace App\Modules\Store\Policies;

use App\Modules\Identity\Models\User;

class StorePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'ViewAny');
    }

    public function view(User $user): bool
    {
        return $this->allows($user, 'View');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'Create');
    }

    public function update(User $user): bool
    {
        return $this->allows($user, 'Update');
    }

    public function delete(User $user): bool
    {
        return false;
    }

    private function allows(User $user, string $ability): bool
    {
        $resource = class_basename(static::class);
        $resource = substr($resource, 0, -strlen('Policy'));

        return $user->isPanelAdministrator()
            || $user->can("{$ability}:{$resource}");
    }
}
