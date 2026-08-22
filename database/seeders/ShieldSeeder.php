<?php

namespace Database\Seeders;

use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $tenants = '[]';
        $users = '[]';
        $userTenantPivot = '[]';
        $rolesWithPermissions = '[{"name":"super_admin","guard_name":"web","permissions":["ViewAny:Role","View:Role","Create:Role","Update:Role","Delete:Role","DeleteAny:Role","Restore:Role","ForceDelete:Role","ForceDeleteAny:Role","RestoreAny:Role","Replicate:Role","Reorder:Role","ViewAny:User","View:User","Create:User","Update:User","Delete:User","DeleteAny:User","Restore:User","ForceDelete:User","ForceDeleteAny:User","RestoreAny:User","Replicate:User","Reorder:User","ViewAny:AffiliateCommission","View:AffiliateCommission","Create:AffiliateCommission","Update:AffiliateCommission","Delete:AffiliateCommission","DeleteAny:AffiliateCommission","Restore:AffiliateCommission","ForceDelete:AffiliateCommission","ForceDeleteAny:AffiliateCommission","RestoreAny:AffiliateCommission","Replicate:AffiliateCommission","Reorder:AffiliateCommission","ViewAny:AffiliatePayoutRequest","View:AffiliatePayoutRequest","Create:AffiliatePayoutRequest","Update:AffiliatePayoutRequest","Delete:AffiliatePayoutRequest","DeleteAny:AffiliatePayoutRequest","Restore:AffiliatePayoutRequest","ForceDelete:AffiliatePayoutRequest","ForceDeleteAny:AffiliatePayoutRequest","RestoreAny:AffiliatePayoutRequest","Replicate:AffiliatePayoutRequest","Reorder:AffiliatePayoutRequest","ViewAny:Affiliate","View:Affiliate","Create:Affiliate","Update:Affiliate","Delete:Affiliate","DeleteAny:Affiliate","Restore:Affiliate","ForceDelete:Affiliate","ForceDeleteAny:Affiliate","RestoreAny:Affiliate","Replicate:Affiliate","Reorder:Affiliate","ViewAny:BlogPostCategory","View:BlogPostCategory","Create:BlogPostCategory","Update:BlogPostCategory","Delete:BlogPostCategory","DeleteAny:BlogPostCategory","Restore:BlogPostCategory","ForceDelete:BlogPostCategory","ForceDeleteAny:BlogPostCategory","RestoreAny:BlogPostCategory","Replicate:BlogPostCategory","Reorder:BlogPostCategory","ViewAny:BlogPost","View:BlogPost","Create:BlogPost","Update:BlogPost","Delete:BlogPost","DeleteAny:BlogPost","Restore:BlogPost","ForceDelete:BlogPost","ForceDeleteAny:BlogPost","RestoreAny:BlogPost","Replicate:BlogPost","Reorder:BlogPost","ViewAny:Booking","View:Booking","Create:Booking","Update:Booking","Delete:Booking","DeleteAny:Booking","Restore:Booking","ForceDelete:Booking","ForceDeleteAny:Booking","RestoreAny:Booking","Replicate:Booking","Reorder:Booking","ViewAny:Coupon","View:Coupon","Create:Coupon","Update:Coupon","Delete:Coupon","DeleteAny:Coupon","Restore:Coupon","ForceDelete:Coupon","ForceDeleteAny:Coupon","RestoreAny:Coupon","Replicate:Coupon","Reorder:Coupon","ViewAny:Customer","View:Customer","Create:Customer","Update:Customer","Delete:Customer","DeleteAny:Customer","Restore:Customer","ForceDelete:Customer","ForceDeleteAny:Customer","RestoreAny:Customer","Replicate:Customer","Reorder:Customer","ViewAny:Discount","View:Discount","Create:Discount","Update:Discount","Delete:Discount","DeleteAny:Discount","Restore:Discount","ForceDelete:Discount","ForceDeleteAny:Discount","RestoreAny:Discount","Replicate:Discount","Reorder:Discount","ViewAny:EventPaymentPlan","View:EventPaymentPlan","Create:EventPaymentPlan","Update:EventPaymentPlan","Delete:EventPaymentPlan","DeleteAny:EventPaymentPlan","Restore:EventPaymentPlan","ForceDelete:EventPaymentPlan","ForceDeleteAny:EventPaymentPlan","RestoreAny:EventPaymentPlan","Replicate:EventPaymentPlan","Reorder:EventPaymentPlan","ViewAny:Event","View:Event","Create:Event","Update:Event","Delete:Event","DeleteAny:Event","Restore:Event","ForceDelete:Event","ForceDeleteAny:Event","RestoreAny:Event","Replicate:Event","Reorder:Event","ViewAny:LedgerAccount","View:LedgerAccount","Create:LedgerAccount","Update:LedgerAccount","Delete:LedgerAccount","DeleteAny:LedgerAccount","Restore:LedgerAccount","ForceDelete:LedgerAccount","ForceDeleteAny:LedgerAccount","RestoreAny:LedgerAccount","Replicate:LedgerAccount","Reorder:LedgerAccount","ViewAny:LedgerTransaction","View:LedgerTransaction","Create:LedgerTransaction","Update:LedgerTransaction","Delete:LedgerTransaction","DeleteAny:LedgerTransaction","Restore:LedgerTransaction","ForceDelete:LedgerTransaction","ForceDeleteAny:LedgerTransaction","RestoreAny:LedgerTransaction","Replicate:LedgerTransaction","Reorder:LedgerTransaction","ViewAny:PaymentRefund","View:PaymentRefund","Create:PaymentRefund","Update:PaymentRefund","Delete:PaymentRefund","DeleteAny:PaymentRefund","Restore:PaymentRefund","ForceDelete:PaymentRefund","ForceDeleteAny:PaymentRefund","RestoreAny:PaymentRefund","Replicate:PaymentRefund","Reorder:PaymentRefund","ViewAny:Payment","View:Payment","Create:Payment","Update:Payment","Delete:Payment","DeleteAny:Payment","Restore:Payment","ForceDelete:Payment","ForceDeleteAny:Payment","RestoreAny:Payment","Replicate:Payment","Reorder:Payment","ViewAny:PublicPage","View:PublicPage","Create:PublicPage","Update:PublicPage","Delete:PublicPage","DeleteAny:PublicPage","Restore:PublicPage","ForceDelete:PublicPage","ForceDeleteAny:PublicPage","RestoreAny:PublicPage","Replicate:PublicPage","Reorder:PublicPage","ViewAny:Category","View:Category","Create:Category","Update:Category","Delete:Category","DeleteAny:Category","Restore:Category","ForceDelete:Category","ForceDeleteAny:Category","RestoreAny:Category","Replicate:Category","Reorder:Category","ViewAny:ProductOption","View:ProductOption","Create:ProductOption","Update:ProductOption","Delete:ProductOption","DeleteAny:ProductOption","Restore:ProductOption","ForceDelete:ProductOption","ForceDeleteAny:ProductOption","RestoreAny:ProductOption","Replicate:ProductOption","Reorder:ProductOption","ViewAny:Order","View:Order","Create:Order","Update:Order","Delete:Order","DeleteAny:Order","Restore:Order","ForceDelete:Order","ForceDeleteAny:Order","RestoreAny:Order","Replicate:Order","Reorder:Order","ViewAny:Product","View:Product","Create:Product","Update:Product","Delete:Product","DeleteAny:Product","Restore:Product","ForceDelete:Product","ForceDeleteAny:Product","RestoreAny:Product","Replicate:Product","Reorder:Product","View:ManageAboutPage","View:ManageContactPage","View:ManageGeneralSettings","View:ManageStoreSettings","View:MediaManager"]}]';
        $directPermissions = '[]';

        // 1. Seed tenants first (if present)
        if (! blank($tenants) && $tenants !== '[]') {
            static::seedTenants($tenants);
        }

        // 2. Seed roles with permissions
        static::makeRolesWithPermissions($rolesWithPermissions);

        // 3. Seed direct permissions
        static::makeDirectPermissions($directPermissions);

        // 4. Seed users with their roles/permissions (if present)
        if (! blank($users) && $users !== '[]') {
            static::seedUsers($users);
        }

        // 5. Seed user-tenant pivot (if present)
        if (! blank($userTenantPivot) && $userTenantPivot !== '[]') {
            static::seedUserTenantPivot($userTenantPivot);
        }

        $this->command->info('Shield Seeding Completed.');
    }

    protected static function seedTenants(string $tenants): void
    {
        if (blank($tenantData = json_decode($tenants, true))) {
            return;
        }

        $tenantModel = '';
        if (blank($tenantModel)) {
            return;
        }

        foreach ($tenantData as $tenant) {
            $tenantModel::firstOrCreate(
                ['id' => $tenant['id']],
                $tenant
            );
        }
    }

    protected static function seedUsers(string $users): void
    {
        if (blank($userData = json_decode($users, true))) {
            return;
        }

        $userModel = 'App\Modules\Identity\Models\User';
        $tenancyEnabled = false;

        foreach ($userData as $data) {
            // Extract role/permission data before creating user
            $roles = $data['roles'] ?? [];
            $permissions = $data['permissions'] ?? [];
            $tenantRoles = $data['tenant_roles'] ?? [];
            $tenantPermissions = $data['tenant_permissions'] ?? [];
            unset($data['roles'], $data['permissions'], $data['tenant_roles'], $data['tenant_permissions']);

            $user = $userModel::firstOrCreate(
                ['email' => $data['email']],
                $data
            );

            // Handle tenancy mode - sync roles/permissions per tenant
            if ($tenancyEnabled && (! empty($tenantRoles) || ! empty($tenantPermissions))) {
                foreach ($tenantRoles as $tenantId => $roleNames) {
                    $contextId = $tenantId === '_global' ? null : $tenantId;
                    setPermissionsTeamId($contextId);
                    $user->syncRoles($roleNames);
                }

                foreach ($tenantPermissions as $tenantId => $permissionNames) {
                    $contextId = $tenantId === '_global' ? null : $tenantId;
                    setPermissionsTeamId($contextId);
                    $user->syncPermissions($permissionNames);
                }
            } else {
                // Non-tenancy mode
                if (! empty($roles)) {
                    $user->syncRoles($roles);
                }

                if (! empty($permissions)) {
                    $user->syncPermissions($permissions);
                }
            }
        }
    }

    protected static function seedUserTenantPivot(string $pivot): void
    {
        if (blank($pivotData = json_decode($pivot, true))) {
            return;
        }

        $pivotTable = '';
        if (blank($pivotTable)) {
            return;
        }

        foreach ($pivotData as $row) {
            $uniqueKeys = [];

            if (isset($row['user_id'])) {
                $uniqueKeys['user_id'] = $row['user_id'];
            }

            $tenantForeignKey = 'team_id';
            if (! blank($tenantForeignKey) && isset($row[$tenantForeignKey])) {
                $uniqueKeys[$tenantForeignKey] = $row[$tenantForeignKey];
            }

            if (! empty($uniqueKeys)) {
                DB::table($pivotTable)->updateOrInsert($uniqueKeys, $row);
            }
        }
    }

    protected static function makeRolesWithPermissions(string $rolesWithPermissions): void
    {
        if (blank($rolePlusPermissions = json_decode($rolesWithPermissions, true))) {
            return;
        }

        /** @var Model $roleModel */
        $roleModel = Utils::getRoleModel();
        /** @var Model $permissionModel */
        $permissionModel = Utils::getPermissionModel();

        $tenancyEnabled = false;
        $teamForeignKey = 'team_id';

        foreach ($rolePlusPermissions as $rolePlusPermission) {
            $tenantId = $rolePlusPermission[$teamForeignKey] ?? null;

            // Set tenant context for role creation and permission sync
            if ($tenancyEnabled) {
                setPermissionsTeamId($tenantId);
            }

            $roleData = [
                'name' => $rolePlusPermission['name'],
                'guard_name' => $rolePlusPermission['guard_name'],
            ];

            // Include tenant ID in role data (can be null for global roles)
            if ($tenancyEnabled && ! blank($teamForeignKey)) {
                $roleData[$teamForeignKey] = $tenantId;
            }

            $role = $roleModel::firstOrCreate($roleData);

            if (! blank($rolePlusPermission['permissions'])) {
                $permissionModels = collect($rolePlusPermission['permissions'])
                    ->map(fn ($permission) => $permissionModel::firstOrCreate([
                        'name' => $permission,
                        'guard_name' => $rolePlusPermission['guard_name'],
                    ]))
                    ->all();

                $role->syncPermissions($permissionModels);
            }
        }
    }

    public static function makeDirectPermissions(string $directPermissions): void
    {
        if (blank($permissions = json_decode($directPermissions, true))) {
            return;
        }

        /** @var Model $permissionModel */
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
