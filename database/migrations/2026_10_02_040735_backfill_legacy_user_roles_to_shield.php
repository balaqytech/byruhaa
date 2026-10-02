<?php

use App\Modules\Identity\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $roleIds = [];
        $superAdministratorRoleName = config('filament-shield.super_admin.name');
        $userMorphClass = (new User)->getMorphClass();

        foreach (['staff', $superAdministratorRoleName] as $roleName) {
            DB::table('roles')->insertOrIgnore([
                'name' => $roleName,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $roleIds[$roleName] = DB::table('roles')
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->value('id');
        }

        foreach (config('filament-shield.staff_permissions') as $permissionName) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permissionName,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('role_has_permissions')->insertOrIgnore([
                'role_id' => $roleIds['staff'],
                'permission_id' => DB::table('permissions')
                    ->where('name', $permissionName)
                    ->where('guard_name', 'web')
                    ->value('id'),
            ]);
        }

        DB::table('users')
            ->select(['id', 'role'])
            ->whereIn('role', ['staff', 'admin'])
            ->orderBy('id')
            ->chunkById(500, static function ($users) use ($roleIds, $superAdministratorRoleName, $userMorphClass): void {
                $assignments = [];

                foreach ($users as $user) {
                    $assignments[] = [
                        'role_id' => $roleIds[$user->role === 'admin' ? $superAdministratorRoleName : 'staff'],
                        'model_type' => $userMorphClass,
                        'model_id' => $user->id,
                    ];
                }

                DB::table('model_has_roles')->insertOrIgnore($assignments);
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Existing Shield role assignments are intentionally preserved.
    }
};
