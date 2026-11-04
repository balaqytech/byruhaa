<?php

namespace App\Modules\Content\Policies;

use App\Modules\Identity\Models\User;

class PublicPagePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'ViewAny:PublicPage');
    }

    public function view(User $user): bool
    {
        return $this->allows($user, 'View:PublicPage');
    }

    public function update(User $user): bool
    {
        return $this->allows($user, 'Update:PublicPage');
    }

    public function delete(User $user): bool
    {
        return false;
    }

    private function allows(User $user, string $permission): bool
    {
        return $user->isPanelAdministrator()
            || $user->can($permission);
    }
}
