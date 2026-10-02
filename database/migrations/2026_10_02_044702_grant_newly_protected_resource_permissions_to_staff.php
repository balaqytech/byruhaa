<?php

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
        $staffRoleId = DB::table('roles')
            ->where('name', 'staff')
            ->where('guard_name', 'web')
            ->value('id');

        if ($staffRoleId === null) {
            return;
        }

        foreach (config('filament-shield.staff_permissions') as $permissionName) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $permissionName,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('role_has_permissions')->insertOrIgnore([
                'role_id' => $staffRoleId,
                'permission_id' => DB::table('permissions')
                    ->where('name', $permissionName)
                    ->where('guard_name', 'web')
                    ->value('id'),
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Existing staff permissions are intentionally preserved.
    }
};
