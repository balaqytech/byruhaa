<?php

use App\Filament\Resources\AffiliateCommissions\AffiliateCommissionResource;
use App\Filament\Resources\MinorProfiles\MinorProfileResource;
use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\User;
use BezhanSalleh\FilamentShield\FilamentShield;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Database\Seeders\ShieldSeeder;
use Spatie\Permission\Models\Permission;

test('Shield role editor exposes workspace and card permissions without unused resource actions', function (): void {
    $shield = app(FilamentShield::class);
    $customPermissions = $shield->getCustomPermissions();
    $resourcePermissions = $shield->getResources();

    expect(config('filament-shield.shield_resource.tabs.custom_permissions'))->toBeTrue()
        ->and(array_keys($customPermissions))->toContain(
            'Sell:Pos', 'Reprint:PosReceipts', 'View:BaristaBoard', 'Prepare:BaristaOrders',
            'View:PickupBoard', 'Complete:PickupOrders', 'Issue:PosCards', 'Print:PosCards',
            'Adjust:ProductStock',
        )
        ->and(collect($resourcePermissions[AffiliateCommissionResource::class]['permissions'])->pluck('key')->all())
        ->toBe(['ViewAny:AffiliateCommission', 'View:AffiliateCommission'])
        ->and(collect($resourcePermissions[MinorProfileResource::class]['permissions'])->pluck('key')->all())
        ->toBe(['ViewAny:MinorProfile', 'View:MinorProfile', 'Create:MinorProfile'])
        ->and(collect($shield->getEntitiesPermissions())->filter(fn (string $name): bool => preg_match('/^(DeleteAny|Restore|RestoreAny|ForceDelete|ForceDeleteAny|Replicate|Reorder):/', $name) === 1)->all())
        ->toBe([]);

    $administrator = User::factory()->admin()->create();
    $response = $this->actingAs($administrator, 'web')->get(RoleResource::getUrl('create'));
    $response->assertSuccessful();
    expect(str_contains($response->getContent(), 'Sell:Pos'))->toBeTrue()
        ->and(str_contains($response->getContent(), 'Issue:PosCards'))->toBeTrue();
});

test('Shield seeding creates workspace roles and exact custom permissions idempotently', function (): void {
    $this->seed(ShieldSeeder::class);
    $count = Permission::query()->count();
    $this->seed(ShieldSeeder::class);

    expect(Permission::query()->count())->toBe($count)
        ->and(Role::findByName('pos_cashier', 'web')->hasPermissionTo('Sell:Pos'))->toBeTrue()
        ->and(Role::findByName('pos_card_manager', 'web')->hasPermissionTo('Issue:PosCards'))->toBeTrue()
        ->and(Role::findByName('pos_card_manager', 'web')->hasPermissionTo('Print:PosCards'))->toBeTrue()
        ->and(Permission::findByName('Adjust:ProductStock', 'web')->exists)->toBeTrue()
        ->and(Permission::query()->where('name', 'DeleteAny:AffiliateCommission')->exists())->toBeFalse();
});
