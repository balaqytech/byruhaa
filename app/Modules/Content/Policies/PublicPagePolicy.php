<?php

namespace App\Modules\Content\Policies;

use BackedEnum;
use Illuminate\Contracts\Auth\Authenticatable;

class PublicPagePolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return $this->allows($user);
    }

    public function view(Authenticatable $user): bool
    {
        return $this->allows($user);
    }

    public function update(Authenticatable $user): bool
    {
        return $this->allows($user);
    }

    public function delete(Authenticatable $user): bool
    {
        return false;
    }

    private function allows(Authenticatable $user): bool
    {
        $role = $user->getAttribute('role');
        $roleValue = $role instanceof BackedEnum ? $role->value : $role;

        return in_array($roleValue, ['staff', 'admin'], true);
    }
}
