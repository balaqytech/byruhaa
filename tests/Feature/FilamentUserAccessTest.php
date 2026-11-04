<?php

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Modules\Identity\Actions\SyncUserRoles;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Store\Models\Product;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\ListRoles;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tapp\FilamentAuditing\Filament\Resources\Audits\AuditResource;

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

    $this->get(AuditResource::getUrl('index'))
        ->assertSuccessful();

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Content Manager',
            'email' => 'content.manager@example.test',
            'password' => 'password',
            'roles' => [$role->getKey()],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $managedUser = User::query()
        ->where('email', 'content.manager@example.test')
        ->firstOrFail();

    expect(Hash::check('password', $managedUser->password))->toBeTrue()
        ->and($managedUser->hasRole($role))->toBeTrue();
});

test('staff cannot access user or role management', function () {
    $staff = User::factory()->create();

    $this->actingAs($staff, 'web')
        ->get(UserResource::getUrl('index'))
        ->assertForbidden();

    $this->get(RoleResource::getUrl('index'))
        ->assertForbidden();

    $this->get(AuditResource::getUrl('index'))
        ->assertForbidden();
});

test('audit detail access follows audit permissions', function () {
    config(['audit.console' => true]);

    $administrator = User::factory()->admin()->create();
    $auditedUser = User::factory()->create();
    $audit = $auditedUser->audits()->latest()->firstOrFail();
    $auditUrl = AuditResource::getUrl('view', ['record' => $audit]);

    $this->actingAs($administrator, 'web')
        ->get($auditUrl)
        ->assertSuccessful();

    $staff = User::factory()->create();

    $this->actingAs($staff, 'web')
        ->get($auditUrl)
        ->assertForbidden();
});

test('Shield permissions can grant user management without the super admin role', function () {
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

test('delegated Shield permissions cannot grant administrator access or manage roles', function () {
    $staff = User::factory()->create();
    foreach (['Create:User', 'Update:User', 'Delete:User', 'Create:Role', 'Update:Role', 'Delete:Role'] as $name) {
        $staff->givePermissionTo(Permission::findOrCreate($name, 'web'));
    }

    $administrator = User::factory()->admin()->create();
    $role = Role::create(['name' => 'operator', 'guard_name' => 'web']);

    expect($staff->can('create', User::class))->toBeFalse()
        ->and($staff->can('update', $administrator))->toBeFalse()
        ->and($staff->can('delete', $administrator))->toBeFalse()
        ->and($staff->can('create', Role::class))->toBeFalse()
        ->and($staff->can('update', $role))->toBeFalse()
        ->and($staff->can('delete', $role))->toBeFalse();

    $this->actingAs($staff, 'web')
        ->get(UserResource::getUrl('create'))
        ->assertForbidden();
});

test('an administrator cannot delete themselves or the protected super admin role', function () {
    $administrator = User::factory()->admin()->create();
    $superAdminRole = Role::findOrCreate('super_admin', 'web');

    expect($administrator->can('delete', $administrator))->toBeFalse()
        ->and($administrator->can('delete', $superAdminRole))->toBeFalse();
});

test('super administrators see user and role create actions', function () {
    $superAdministrator = User::factory()->create();
    $superAdministrator->assignRole(Role::findOrCreate('super_admin', 'web'));

    $this->actingAs($superAdministrator, 'web');

    Livewire::test(ListUsers::class)
        ->assertActionVisible('create');

    Livewire::test(ListRoles::class)
        ->assertActionVisible('create');
});

test('user resource labels are translated into Arabic', function () {
    app()->setLocale('ar');

    expect(UserResource::getNavigationGroup())->toBe('إدارة المستخدمين')
        ->and(UserResource::getPluralModelLabel())->toBe('المستخدمون');
});

test('panel access requires a Shield role or direct permission', function () {
    $user = User::factory()->create();

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();

    $user->syncRoles([]);

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();

    $user->givePermissionTo(Permission::findOrCreate('ViewAny:User', 'web'));

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
});

test('admin dashboard links to the three separate staff workspaces', function () {
    $administrator = User::factory()->admin()->create();

    $this->actingAs($administrator, 'web')
        ->get(route('filament.admin.pages.dashboard'))
        ->assertSuccessful()
        ->assertSee('نقطة البيع')
        ->assertSee('شاشة الباريستا')
        ->assertSee('نقطة التسليم')
        ->assertSee(route('cashier.login'), false)
        ->assertSee(route('barista.login'), false)
        ->assertSee(route('pickup.login'), false);

    Livewire::test(Dashboard::class)
        ->assertActionExists('cashierWorkspace')
        ->assertActionExists('baristaWorkspace')
        ->assertActionExists('pickupWorkspace');
});

test('pickup-only staff cannot access the Filament admin panel', function () {
    $attendant = User::factory()->create();
    $attendant->syncRoles(Role::findOrCreate('pos_pickup_attendant', 'web'));
    $attendant->givePermissionTo(Permission::findOrCreate('View:PickupBoard', 'web'));
    $attendant->givePermissionTo(Permission::findOrCreate('Complete:PickupOrders', 'web'));

    expect($attendant->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
    $this->actingAs($attendant, 'web')->get(route('filament.admin.pages.dashboard'))->assertForbidden();
});

test('a custom role with only workspace permissions cannot enter Filament', function (): void {
    $operator = User::factory()->create();
    $operator->syncRoles(Role::findOrCreate('tablet_operator', 'web'));
    $operator->givePermissionTo(Permission::findOrCreate('View:BaristaBoard', 'web'));

    expect($operator->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
    $this->actingAs($operator, 'web')->get(route('filament.admin.pages.dashboard'))->assertForbidden();
});

test('staff store access follows permissions assigned in Shield', function () {
    $staff = User::factory()->create();

    expect($staff->can('viewAny', Product::class))->toBeTrue();

    $staff->roles()->firstOrFail()->revokePermissionTo('ViewAny:Product');

    expect($staff->fresh()->can('viewAny', Product::class))->toBeFalse();
});

test('the last super administrator cannot remove their own Shield role', function () {
    $administrator = User::factory()->admin()->create();
    $staffRole = Role::findOrCreate('staff', 'web');

    expect(fn () => (new SyncUserRoles)->execute($administrator, [$staffRole->getKey()]))
        ->toThrow(ValidationException::class);

    expect($administrator->fresh()->isPanelAdministrator())->toBeTrue();
});
