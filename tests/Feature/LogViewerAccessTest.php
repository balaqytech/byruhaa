<?php

use App\Modules\Identity\Models\User;
use Spatie\Permission\Models\Permission;

test('administrators can view the log viewer in production', function () {
    config(['app.env' => 'production']);

    $response = $this->actingAs(User::factory()->admin()->create())
        ->get('/log-viewer');

    $response->assertSuccessful();
});

test('staff cannot view the log viewer in production', function () {
    config(['app.env' => 'production']);

    $response = $this->actingAs(User::factory()->create())
        ->get('/log-viewer');

    $response->assertForbidden();
});

test('the Shield log viewer permission grants access', function () {
    config(['app.env' => 'production']);

    $user = User::factory()->create();
    $permission = Permission::create([
        'name' => 'View:LogViewer',
        'guard_name' => 'web',
    ]);
    $user->givePermissionTo($permission);

    $response = $this->actingAs($user)
        ->get('/log-viewer');

    $response->assertSuccessful();
});
