<?php

use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\UserResource;
use App\Modules\Identity\Models\User;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('administrators can create users and assign Shield roles', function () {
    $administrator = User::factory()->admin()->create();
    $role = Role::create([
        'name' => 'content_manager',
        'guard_name' => 'web',
    ]);

    $this->actingAs($administrator, 'web')
        ->get(UserResource::getUrl('index'))
        ->assertSuccessful()
        ->assertSee($administrator->email);

    $this->get(RoleResource::getUrl('index'))
        ->assertSuccessful();

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Content Manager',
            'email' => 'content.manager@example.test',
            'password' => 'password',
            'role' => UserRole::Staff->value,
            'roles' => [$role->getKey()],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $managedUser = User::query()
        ->where('email', 'content.manager@example.test')
        ->firstOrFail();

    expect($managedUser->role)
        ->toBe(UserRole::Staff)
        ->and(Hash::check('password', $managedUser->password))->toBeTrue()
        ->and($managedUser->hasRole($role))->toBeTrue();
});

test('staff cannot access user or role management', function () {
    $staff = User::factory()->create();

    $this->actingAs($staff, 'web')
        ->get(UserResource::getUrl('index'))
        ->assertForbidden();

    $this->get(RoleResource::getUrl('index'))
        ->assertForbidden();
});

test('Shield permissions can grant user management without the legacy admin role', function () {
    $staff = User::factory()->create();
    $role = Role::create([
        'name' => 'user_viewer',
        'guard_name' => 'web',
    ]);
    $permission = Permission::create([
        'name' => 'ViewAny:User',
        'guard_name' => 'web',
    ]);
    $role->givePermissionTo($permission);
    $staff->assignRole($role);

    $this->actingAs($staff, 'web')
        ->get(UserResource::getUrl('index'))
        ->assertSuccessful();

    $this->get(UserResource::getUrl('create'))
        ->assertForbidden();
});
