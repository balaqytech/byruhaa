<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Models\User;

final class SyncUserRoles
{
    /**
     * @param  array<int, int|string|null>  $roleIds
     */
    public function execute(User $user, array $roleIds): void
    {
        $roleIds = collect($roleIds)
            ->filter(fn (mixed $roleId): bool => filled($roleId))
            ->map(fn (mixed $roleId): int|string => $roleId)
            ->values()
            ->all();

        $roles = $user->roles();
        $roleKey = $roles->getRelated()->getQualifiedKeyName();
        $currentRoleIds = $roles
            ->pluck($roleKey)
            ->map(fn (mixed $roleId): string => (string) $roleId)
            ->sort()
            ->values()
            ->all();
        $newRoleIds = collect($roleIds)
            ->map(fn (int|string $roleId): string => (string) $roleId)
            ->sort()
            ->values()
            ->all();

        if ($currentRoleIds === $newRoleIds) {
            return;
        }

        $user->auditSync(
            'roles',
            $roleIds,
            true,
            ['id', 'name', 'guard_name'],
        );
    }
}
