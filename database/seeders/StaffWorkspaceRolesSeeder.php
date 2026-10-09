<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class StaffWorkspaceRolesSeeder extends Seeder
{
    public function run(): void
    {
        $cashierPermission = Permission::findOrCreate('Sell:Pos', 'web');
        $baristaPermission = Permission::findOrCreate('View:BaristaBoard', 'web');
        $prepareOrdersPermission = Permission::findOrCreate('Prepare:BaristaOrders', 'web');
        $reprintReceiptsPermission = Permission::findOrCreate('Reprint:PosReceipts', 'web');
        $pickupBoardPermission = Permission::findOrCreate('View:PickupBoard', 'web');
        $completeOrdersPermission = Permission::findOrCreate('Complete:PickupOrders', 'web');
        Permission::findOrCreate('Manage:PosCards', 'web');
        $issueCardsPermission = Permission::findOrCreate('Issue:PosCards', 'web');
        $printCardsPermission = Permission::findOrCreate('Print:PosCards', 'web');
        Permission::findOrCreate('Adjust:ProductStock', 'web');

        Role::findOrCreate('pos_cashier', 'web')->givePermissionTo($cashierPermission, $reprintReceiptsPermission);
        Role::findOrCreate('pos_barista', 'web')->givePermissionTo($baristaPermission, $prepareOrdersPermission);
        Role::findOrCreate('pos_pickup_attendant', 'web')->givePermissionTo($pickupBoardPermission, $completeOrdersPermission);
        Role::findOrCreate('pos_card_manager', 'web')->givePermissionTo($issueCardsPermission, $printCardsPermission);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
