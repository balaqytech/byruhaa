<?php

use App\Filament\Pages\ManageGeneralSettings;
use App\Filament\Pages\MediaManager;
use App\Filament\Resources\Bookings\BookingResource;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Store\Filament\Resources\Products\ProductResource;
use App\Modules\Store\Models\Product;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Permission;

test('a custom role without permissions cannot see or open resources and pages', function () {
    $user = User::factory()->create();
    $user->syncRoles(Role::findOrCreate('limited_operator', 'web'));

    $this->actingAs($user, 'web');

    foreach (Filament::getPanel('admin')->getResources() as $resource) {
        expect($resource::canViewAny())->toBeFalse();
        $this->get($resource::getUrl('index'))->assertForbidden();
    }

    expect(ManageGeneralSettings::shouldRegisterNavigation())->toBeFalse()
        ->and(MediaManager::shouldRegisterNavigation())->toBeFalse();

    $this->get(ManageGeneralSettings::getUrl())->assertForbidden();
    $this->get(MediaManager::getUrl())->assertForbidden();
    $this->get(BookingResource::getUrl('create'))->assertForbidden();
});

test('settings and media pages become available only with their page permissions', function () {
    $user = User::factory()->create();
    $role = Role::findOrCreate('settings_viewer', 'web');
    $role->givePermissionTo(Permission::findOrCreate('View:ManageGeneralSettings', 'web'));
    $user->syncRoles($role);

    $this->actingAs($user, 'web');

    expect(ManageGeneralSettings::shouldRegisterNavigation())->toBeTrue()
        ->and(MediaManager::shouldRegisterNavigation())->toBeFalse();

    $this->get(ManageGeneralSettings::getUrl())->assertSuccessful();
    $this->get(MediaManager::getUrl())->assertForbidden();
});

test('a custom role only gains the resource abilities explicitly assigned to it', function () {
    $user = User::factory()->create();
    $role = Role::findOrCreate('catalog_viewer', 'web');
    $role->givePermissionTo(Permission::findOrCreate('ViewAny:Product', 'web'));
    $user->syncRoles($role);

    $this->actingAs($user, 'web');

    expect(ProductResource::canViewAny())->toBeTrue()
        ->and(ProductResource::canCreate())->toBeFalse()
        ->and($user->can('viewAny', Product::class))->toBeTrue()
        ->and(BookingResource::canViewAny())->toBeFalse();

    $this->get(ProductResource::getUrl('index'))->assertSuccessful();
    $this->get(ProductResource::getUrl('create'))->assertForbidden();
    $this->get(BookingResource::getUrl('index'))->assertForbidden();
});

test('the compatibility migration preserves staff access without granting custom roles permissions', function () {
    $staffRole = Role::findOrCreate('staff', 'web');
    $customRole = Role::findOrCreate('limited_operator', 'web');
    $staffRole->revokePermissionTo('ViewAny:Booking');

    $staffPermissions = config('filament-shield.staff_permissions');
    config(['filament-shield.staff_permissions' => null]);

    $migration = require database_path('migrations/2026_10_02_044702_grant_newly_protected_resource_permissions_to_staff.php');
    $migration->up();

    config(['filament-shield.staff_permissions' => $staffPermissions]);

    expect($staffRole->fresh()->hasPermissionTo('ViewAny:Booking'))->toBeTrue()
        ->and($customRole->fresh()->hasPermissionTo('ViewAny:Booking'))->toBeFalse();
});
