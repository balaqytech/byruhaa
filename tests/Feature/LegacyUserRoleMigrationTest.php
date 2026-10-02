<?php

use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('legacy users are assigned Shield roles before the role column is removed', function () {
    expect(Schema::hasColumn('users', 'role'))->toBeFalse();

    Schema::table('users', function (Blueprint $table): void {
        $table->string('role')->nullable();
    });

    $staff = User::factory()->create();
    $administrator = User::factory()->create();
    $existingRole = Role::findOrCreate('content_manager', 'web');

    $staff->syncRoles([]);
    $administrator->syncRoles($existingRole);

    DB::table('users')->where('id', $staff->id)->update(['role' => 'staff']);
    DB::table('users')->where('id', $administrator->id)->update(['role' => 'admin']);

    $staffPermissions = config('filament-shield.staff_permissions');
    $superAdministratorRoleName = config('filament-shield.super_admin.name');

    config([
        'filament-shield.staff_permissions' => null,
        'filament-shield.super_admin.name' => null,
    ]);

    $migration = require database_path('migrations/2026_10_02_040735_backfill_legacy_user_roles_to_shield.php');
    $migration->up();

    config([
        'filament-shield.staff_permissions' => $staffPermissions,
        'filament-shield.super_admin.name' => $superAdministratorRoleName,
    ]);

    expect($staff->fresh()->hasRole('staff'))->toBeTrue()
        ->and($staff->fresh()->can('ViewAny:Product'))->toBeTrue()
        ->and($administrator->fresh()->hasRole('super_admin'))->toBeTrue()
        ->and($administrator->fresh()->hasRole($existingRole))->toBeTrue();
});
