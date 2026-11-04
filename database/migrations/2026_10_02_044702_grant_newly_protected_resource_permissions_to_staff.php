<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const RESOURCE_NAMES = [
        'AffiliateCommission', 'AffiliatePayoutRequest', 'Affiliate',
        'BlogPostCategory', 'BlogPost', 'Booking', 'Coupon', 'Customer',
        'Discount', 'EventPaymentPlan', 'Event', 'LedgerAccount',
        'LedgerTransaction', 'PaymentRefund', 'Payment',
    ];

    private const RESOURCE_ACTIONS = ['ViewAny', 'View', 'Create', 'Update', 'Delete'];

    private const PAGE_PERMISSIONS = [
        'View:ManageAboutPage', 'View:ManageContactPage', 'View:ManageGeneralSettings',
        'View:ManageStoreSettings', 'View:MediaManager',
    ];

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

        $permissionNames = self::PAGE_PERMISSIONS;

        foreach (self::RESOURCE_NAMES as $resourceName) {
            foreach (self::RESOURCE_ACTIONS as $action) {
                $permissionNames[] = "{$action}:{$resourceName}";
            }
        }

        foreach ($permissionNames as $permissionName) {
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
