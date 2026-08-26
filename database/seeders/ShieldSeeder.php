<?php

namespace Database\Seeders;

use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $rolesWithPermissions = '[{"name":"super_admin","guard_name":"web","permissions":["ViewAny:Role","View:Role","Create:Role","Update:Role","Delete:Role","DeleteAny:Role","Restore:Role","ForceDelete:Role","ForceDeleteAny:Role","RestoreAny:Role","Replicate:Role","Reorder:Role","ViewAny:User","View:User","Create:User","Update:User","Delete:User","DeleteAny:User","Restore:User","ForceDelete:User","ForceDeleteAny:User","RestoreAny:User","Replicate:User","Reorder:User","ViewAny:AffiliateCommission","View:AffiliateCommission","Create:AffiliateCommission","Update:AffiliateCommission","Delete:AffiliateCommission","DeleteAny:AffiliateCommission","Restore:AffiliateCommission","ForceDelete:AffiliateCommission","ForceDeleteAny:AffiliateCommission","RestoreAny:AffiliateCommission","Replicate:AffiliateCommission","Reorder:AffiliateCommission","ViewAny:AffiliatePayoutRequest","View:AffiliatePayoutRequest","Create:AffiliatePayoutRequest","Update:AffiliatePayoutRequest","Delete:AffiliatePayoutRequest","DeleteAny:AffiliatePayoutRequest","Restore:AffiliatePayoutRequest","ForceDelete:AffiliatePayoutRequest","ForceDeleteAny:AffiliatePayoutRequest","RestoreAny:AffiliatePayoutRequest","Replicate:AffiliatePayoutRequest","Reorder:AffiliatePayoutRequest","ViewAny:Affiliate","View:Affiliate","Create:Affiliate","Update:Affiliate","Delete:Affiliate","DeleteAny:Affiliate","Restore:Affiliate","ForceDelete:Affiliate","ForceDeleteAny:Affiliate","RestoreAny:Affiliate","Replicate:Affiliate","Reorder:Affiliate","ViewAny:BlogPostCategory","View:BlogPostCategory","Create:BlogPostCategory","Update:BlogPostCategory","Delete:BlogPostCategory","DeleteAny:BlogPostCategory","Restore:BlogPostCategory","ForceDelete:BlogPostCategory","ForceDeleteAny:BlogPostCategory","RestoreAny:BlogPostCategory","Replicate:BlogPostCategory","Reorder:BlogPostCategory","ViewAny:BlogPost","View:BlogPost","Create:BlogPost","Update:BlogPost","Delete:BlogPost","DeleteAny:BlogPost","Restore:BlogPost","ForceDelete:BlogPost","ForceDeleteAny:BlogPost","RestoreAny:BlogPost","Replicate:BlogPost","Reorder:BlogPost","ViewAny:Booking","View:Booking","Create:Booking","Update:Booking","Delete:Booking","DeleteAny:Booking","Restore:Booking","ForceDelete:Booking","ForceDeleteAny:Booking","RestoreAny:Booking","Replicate:Booking","Reorder:Booking","ViewAny:Coupon","View:Coupon","Create:Coupon","Update:Coupon","Delete:Coupon","DeleteAny:Coupon","Restore:Coupon","ForceDelete:Coupon","ForceDeleteAny:Coupon","RestoreAny:Coupon","Replicate:Coupon","Reorder:Coupon","ViewAny:Customer","View:Customer","Create:Customer","Update:Customer","Delete:Customer","DeleteAny:Customer","Restore:Customer","ForceDelete:Customer","ForceDeleteAny:Customer","RestoreAny:Customer","Replicate:Customer","Reorder:Customer","ViewAny:Discount","View:Discount","Create:Discount","Update:Discount","Delete:Discount","DeleteAny:Discount","Restore:Discount","ForceDelete:Discount","ForceDeleteAny:Discount","RestoreAny:Discount","Replicate:Discount","Reorder:Discount","ViewAny:EventPaymentPlan","View:EventPaymentPlan","Create:EventPaymentPlan","Update:EventPaymentPlan","Delete:EventPaymentPlan","DeleteAny:EventPaymentPlan","Restore:EventPaymentPlan","ForceDelete:EventPaymentPlan","ForceDeleteAny:EventPaymentPlan","RestoreAny:EventPaymentPlan","Replicate:EventPaymentPlan","Reorder:EventPaymentPlan","ViewAny:Event","View:Event","Create:Event","Update:Event","Delete:Event","DeleteAny:Event","Restore:Event","ForceDelete:Event","ForceDeleteAny:Event","RestoreAny:Event","Replicate:Event","Reorder:Event","ViewAny:LedgerAccount","View:LedgerAccount","Create:LedgerAccount","Update:LedgerAccount","Delete:LedgerAccount","DeleteAny:LedgerAccount","Restore:LedgerAccount","ForceDelete:LedgerAccount","ForceDeleteAny:LedgerAccount","RestoreAny:LedgerAccount","Replicate:LedgerAccount","Reorder:LedgerAccount","ViewAny:LedgerTransaction","View:LedgerTransaction","Create:LedgerTransaction","Update:LedgerTransaction","Delete:LedgerTransaction","DeleteAny:LedgerTransaction","Restore:LedgerTransaction","ForceDelete:LedgerTransaction","ForceDeleteAny:LedgerTransaction","RestoreAny:LedgerTransaction","Replicate:LedgerTransaction","Reorder:LedgerTransaction","ViewAny:PaymentRefund","View:PaymentRefund","Create:PaymentRefund","Update:PaymentRefund","Delete:PaymentRefund","DeleteAny:PaymentRefund","Restore:PaymentRefund","ForceDelete:PaymentRefund","ForceDeleteAny:PaymentRefund","RestoreAny:PaymentRefund","Replicate:PaymentRefund","Reorder:PaymentRefund","ViewAny:Payment","View:Payment","Create:Payment","Update:Payment","Delete:Payment","DeleteAny:Payment","Restore:Payment","ForceDelete:Payment","ForceDeleteAny:Payment","RestoreAny:Payment","Replicate:Payment","Reorder:Payment","ViewAny:PublicPage","View:PublicPage","Create:PublicPage","Update:PublicPage","Delete:PublicPage","DeleteAny:PublicPage","Restore:PublicPage","ForceDelete:PublicPage","ForceDeleteAny:PublicPage","RestoreAny:PublicPage","Replicate:PublicPage","Reorder:PublicPage","ViewAny:Category","View:Category","Create:Category","Update:Category","Delete:Category","DeleteAny:Category","Restore:Category","ForceDelete:Category","ForceDeleteAny:Category","RestoreAny:Category","Replicate:Category","Reorder:Category","ViewAny:ProductOption","View:ProductOption","Create:ProductOption","Update:ProductOption","Delete:ProductOption","DeleteAny:ProductOption","Restore:ProductOption","ForceDelete:ProductOption","ForceDeleteAny:ProductOption","RestoreAny:ProductOption","Replicate:ProductOption","Reorder:ProductOption","ViewAny:Order","View:Order","Create:Order","Update:Order","Delete:Order","DeleteAny:Order","Restore:Order","ForceDelete:Order","ForceDeleteAny:Order","RestoreAny:Order","Replicate:Order","Reorder:Order","ViewAny:Product","View:Product","Create:Product","Update:Product","Delete:Product","DeleteAny:Product","Restore:Product","ForceDelete:Product","ForceDeleteAny:Product","RestoreAny:Product","Replicate:Product","Reorder:Product","View:ManageAboutPage","View:ManageContactPage","View:ManageGeneralSettings","View:ManageStoreSettings","View:MediaManager","View:LogViewer","ViewAny:Audit","View:Audit","Create:Audit","Update:Audit","Delete:Audit","DeleteAny:Audit","Restore:Audit","ForceDelete:Audit","ForceDeleteAny:Audit","RestoreAny:Audit","Replicate:Audit","Reorder:Audit"]}]';
        $directPermissions = '[]';

        static::makeRolesWithPermissions($rolesWithPermissions);
        static::makeDirectPermissions($directPermissions);

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            return;
        }

        /** @var class-string<Role> $roleModel */
        $roleModel = Utils::getRoleModel();
        /** @var class-string<Permission> $permissionModel */
        $permissionModel = Utils::getPermissionModel();

        foreach ($rolePlusPermissions as $rolePlusPermission) {
            $roleData = [
                'name' => $rolePlusPermission['name'],
                'guard_name' => $rolePlusPermission['guard_name'],
            ];

            $role = $roleModel::firstOrCreate($roleData);

            if (! blank($rolePlusPermission['permissions'])) {
                $permissionModels = [];

                foreach ($rolePlusPermission['permissions'] as $permission) {
                    $permissionModels[] = $permissionModel::firstOrCreate([
                        'name' => $permission,
                        'guard_name' => $rolePlusPermission['guard_name'],
                    ]);
                }

                $role->syncPermissions($permissionModels);
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (blank($permissions = json_decode($directPermissions, true))) {
            return;
        }

        /** @var class-string<Permission> $permissionModel */
        $permissionModel = Utils::getPermissionModel();

        foreach ($permissions as $permission) {
            if ($permissionModel::whereName($permission['name'])->doesntExist()) {
                $permissionModel::create([
                    'name' => $permission['name'],
                    'guard_name' => $permission['guard_name'],
                ]);
            }
        }
    }
}
