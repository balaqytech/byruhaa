<?php

namespace App\Modules\Identity\Actions;

use App\Modules\Identity\Models\User;
use Illuminate\Validation\ValidationException;

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

        $superAdministratorRoleId = $roles
            ->where('name', config('filament-shield.super_admin.name'))
            ->value('id');

        if ($superAdministratorRoleId !== null
            && ! in_array((string) $superAdministratorRoleId, $newRoleIds, true)
            && User::role(config('filament-shield.super_admin.name'))->count() <= 1) {
            throw ValidationException::withMessages([
                'roles' => __('admin.user_form.errors.last_super_admin'),
            ]);
        }

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
