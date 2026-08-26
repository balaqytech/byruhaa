<?php

use App\Modules\Identity\Actions\SyncUserRoles;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\Event;
use OwenIt\Auditing\Events\AuditCustom;
use Spatie\Permission\Models\Permission;

test('audited user changes exclude sensitive attributes', function () {
    config(['audit.console' => true]);

    $actor = User::factory()->admin()->create();
    $user = User::factory()->create();
    $user->audits()->delete();

    $this->actingAs($actor, 'web');

    $user->update([
        'name' => 'Updated User',
        'password' => 'new-password',
    ]);

    $audit = $user->audits()->latest()->firstOrFail();

    expect($audit->event)
        ->toBe('updated')
        ->and($audit->new_values)
        ->toHaveKey('name')
        ->not->toHaveKey('password');
});

test('user role synchronization creates a custom audit', function () {
    config(['audit.console' => true]);

    $actor = User::factory()->admin()->create();
    $user = User::factory()->create();
    $oldRole = Role::create([
        'name' => 'legacy_manager',
        'guard_name' => 'web',
    ]);
    $role = Role::create([
        'name' => 'content_manager',
        'guard_name' => 'web',
    ]);
    $user->assignRole($oldRole);
    $user->audits()->delete();

    $this->actingAs($actor, 'web');

    (new SyncUserRoles)->execute($user, [$role->getKey()]);

    $audit = $user->audits()->latest()->firstOrFail();

    expect($audit->event)
        ->toBe('sync')
        ->and($user->hasRole($oldRole))
        ->toBeFalse()
        ->and($user->hasRole($role))
        ->toBeTrue()
        ->and($audit->old_values['roles'][0]['id'])
        ->toBe($oldRole->getKey())
        ->and($audit->new_values['roles'][0]['id'])
        ->toBe($role->getKey());
});

test('role permission assignments create custom audits', function () {
    config(['audit.console' => true]);

    $actor = User::factory()->admin()->create();
    $role = Role::create([
        'name' => 'catalog_manager',
        'guard_name' => 'web',
    ]);
    $permission = Permission::create([
        'name' => 'View:Product',
        'guard_name' => 'web',
    ]);
    $role->audits()->delete();

    $this->actingAs($actor, 'web');

    $role->givePermissionTo($permission);

    $audit = $role->audits()->latest()->firstOrFail();

    expect($audit->event)
        ->toBe('attached')
        ->and($audit->new_values['permissions'][0]['id'])
        ->toBe($permission->getKey());
});

test('custom audit state is reset when dispatch fails', function () {
    $user = User::factory()->create();
    $exception = new RuntimeException('Audit dispatch failed.');

    Event::shouldReceive('dispatch')
        ->once()
        ->withArgs(fn (AuditCustom $event): bool => $event->model === $user)
        ->andThrow($exception);

    expect(fn (): mixed => $user->recordCustomAudit(
        'attached',
        ['roles' => [['id' => 1]]],
        [],
    ))
        ->toThrow($exception)
        ->and($user->isCustomEvent)
        ->toBeFalse()
        ->and($user->auditCustomOld)
        ->toBeNull()
        ->and($user->auditCustomNew)
        ->toBeNull();
});
