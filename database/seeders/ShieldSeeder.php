<?php

namespace Database\Seeders;

use BezhanSalleh\FilamentShield\FilamentShield;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesWithPermissions = json_encode([[
            'name' => 'super_admin',
            'guard_name' => 'web',
            'permissions' => app(FilamentShield::class)->getEntitiesPermissions() ?? [],
        ]], JSON_THROW_ON_ERROR);
        $directPermissions = '[]';

        static::makeRolesWithPermissions($rolesWithPermissions);
        static::makeDirectPermissions($directPermissions);

        $staffPermissions = collect(config('filament-shield.staff_permissions', []))
            ->map(fn (string $name): Permission => Permission::findOrCreate($name, 'web'))
            ->all();
        Role::findOrCreate('staff', 'web')->givePermissionTo($staffPermissions);

        $this->call(StaffWorkspaceRolesSeeder::class);

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            return;
        }

        /** @var class-string<Role> $roleModel */
        $roleModel = Utils::getRoleModel();
        /** @var class-string<Permission> $permissionModel */
        $permissionModel = Utils::getPermissionModel();

        foreach ($rolePlusPermissions as $rolePlusPermission) {
            $roleData = [
                'name' => $rolePlusPermission['name'],
                'guard_name' => $rolePlusPermission['guard_name'],
            ];

            $role = $roleModel::firstOrCreate($roleData);

            if (! blank($rolePlusPermission['permissions'])) {
                $permissionModels = [];

                foreach ($rolePlusPermission['permissions'] as $permission) {
                    $permissionModels[] = $permissionModel::firstOrCreate([
                        'name' => $permission,
                        'guard_name' => $rolePlusPermission['guard_name'],
                    ]);
                }

                $role->syncPermissions($permissionModels);
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (blank($permissions = json_decode($directPermissions, true))) {
            return;
        }

        /** @var class-string<Permission> $permissionModel */
        $permissionModel = Utils::getPermissionModel();

        foreach ($permissions as $permission) {
            if ($permissionModel::whereName($permission['name'])->doesntExist()) {
                $permissionModel::create([
                    'name' => $permission['name'],
                    'guard_name' => $permission['guard_name'],
                ]);
            }
        }
    }
}
