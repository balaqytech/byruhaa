<?php

declare(strict_types=1);
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Filament\Pages\Dashboard;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;

return [

    /*
    |--------------------------------------------------------------------------
    | Shield Resource
    |--------------------------------------------------------------------------
    |
    | Here you may configure the built-in role management resource. You can
    | customize the URL, choose whether to show model paths, group it under
    | a cluster, and decide which permission tabs to display.
    |
    */

    'shield_resource' => [
        'slug' => 'shield/roles',
        'show_model_path' => true,
        'cluster' => null,
        'tabs' => [
            'pages' => true,
            'widgets' => true,
            'resources' => true,
            'custom_permissions' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy
    |--------------------------------------------------------------------------
    |
    | When your application supports teams, Shield will automatically detect
    | and configure the tenant model during setup. This enables tenant-scoped
    | roles and permissions throughout your application.
    |
    */

    'tenant_model' => null,

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | This value contains the class name of your user model. This model will
    | be used for role assignments and must implement the HasRoles trait
    | provided by the Spatie\Permission package.
    |
    */

    'auth_provider_model' => 'App\\Modules\\Identity\\Models\\User',

    /*
    |--------------------------------------------------------------------------
    | Super Admin
    |--------------------------------------------------------------------------
    |
    | Here you may define a super admin that has unrestricted access to your
    | application. You can choose to implement this via Laravel's gate system
    | or as a traditional role with all permissions explicitly assigned.
    |
    */

    'super_admin' => [
        'enabled' => true,
        'name' => 'super_admin',
        'define_via_gate' => false,
        'intercept_gate' => 'before',
    ],

    /*
    |--------------------------------------------------------------------------
    | Panel User
    |--------------------------------------------------------------------------
    |
    | When enabled, Shield will create a basic panel user role that can be
    | assigned to users who should have access to your Filament panels but
    | don't need any specific permissions beyond basic authentication.
    |
    */

    'panel_user' => [
        'enabled' => true,
        'name' => 'panel_user',
    ],

    'staff_permissions' => [
        'ViewAny:Category', 'View:Category', 'Create:Category', 'Update:Category',
        'ViewAny:Product', 'View:Product', 'Create:Product', 'Update:Product',
        'ViewAny:ProductOption', 'View:ProductOption', 'Create:ProductOption', 'Update:ProductOption',
        'ViewAny:Order', 'View:Order', 'Create:Order', 'Update:Order',
        'ViewAny:PublicPage', 'View:PublicPage', 'Update:PublicPage',
        'ViewAny:Wallet', 'ViewAny:MinorProfile',
        'ViewAny:AffiliateCommission', 'View:AffiliateCommission', 'Create:AffiliateCommission', 'Update:AffiliateCommission', 'Delete:AffiliateCommission',
        'ViewAny:AffiliatePayoutRequest', 'View:AffiliatePayoutRequest', 'Create:AffiliatePayoutRequest', 'Update:AffiliatePayoutRequest', 'Delete:AffiliatePayoutRequest',
        'ViewAny:Affiliate', 'View:Affiliate', 'Create:Affiliate', 'Update:Affiliate', 'Delete:Affiliate',
        'ViewAny:BlogPostCategory', 'View:BlogPostCategory', 'Create:BlogPostCategory', 'Update:BlogPostCategory', 'Delete:BlogPostCategory',
        'ViewAny:BlogPost', 'View:BlogPost', 'Create:BlogPost', 'Update:BlogPost', 'Delete:BlogPost',
        'ViewAny:Booking', 'View:Booking', 'Create:Booking', 'Update:Booking', 'Delete:Booking',
        'ViewAny:Coupon', 'View:Coupon', 'Create:Coupon', 'Update:Coupon', 'Delete:Coupon',
        'ViewAny:Customer', 'View:Customer', 'Create:Customer', 'Update:Customer', 'Delete:Customer',
        'ViewAny:Discount', 'View:Discount', 'Create:Discount', 'Update:Discount', 'Delete:Discount',
        'ViewAny:EventPaymentPlan', 'View:EventPaymentPlan', 'Create:EventPaymentPlan', 'Update:EventPaymentPlan', 'Delete:EventPaymentPlan',
        'ViewAny:Event', 'View:Event', 'Create:Event', 'Update:Event', 'Delete:Event',
        'ViewAny:LedgerAccount', 'View:LedgerAccount', 'Create:LedgerAccount', 'Update:LedgerAccount', 'Delete:LedgerAccount',
        'ViewAny:LedgerTransaction', 'View:LedgerTransaction', 'Create:LedgerTransaction', 'Update:LedgerTransaction', 'Delete:LedgerTransaction',
        'ViewAny:PaymentRefund', 'View:PaymentRefund', 'Create:PaymentRefund', 'Update:PaymentRefund', 'Delete:PaymentRefund',
        'ViewAny:Payment', 'View:Payment', 'Create:Payment', 'Update:Payment', 'Delete:Payment',
        'View:ManageAboutPage', 'View:ManageContactPage', 'View:ManageGeneralSettings', 'View:ManageStoreSettings', 'View:MediaManager',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Builder
    |--------------------------------------------------------------------------
    |
    | You can customize how permission keys are generated to match your
    | preferred naming convention and organizational standards. Shield uses
    | these settings when creating permission names from your resources.
    |
    | Supported formats: snake, kebab, pascal, camel, upper_snake, lower_snake
    |
    | Note: The separator must not conflict with the case format's own
    | delimiter. For example, `_` cannot be used with snake/lower_snake/
    | upper_snake, and `-` cannot be used with kebab.
    |
    | When `format_custom_permission_keys` is true (default), custom
    | permissions defined below will have their keys formatted according to
    | the case setting. If your custom permissions come from external sources
    | (e.g. Terraform, Keycloak) and must remain unchanged, set this to false.
    | When using the separator in custom permission definitions, each segment
    | will be formatted independently (e.g. 'view:system_log' with pascal
    | case becomes 'View:SystemLog').
    |
    */

    'permissions' => [
        'separator' => ':',
        'case' => 'pascal',
        'generate' => true,
        'format_custom_permission_keys' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Policies
    |--------------------------------------------------------------------------
    |
    | Shield can automatically generate Laravel policies for your resources.
    | Generated policies mirror each model's location: models under
    | app/Models map into the path below (keeping their nesting), models in
    | any other "Models" directory get a sibling "Policies" directory, and
    | vendor models fall back to the path below. When merge is enabled, the
    | methods below will be combined with any resource-specific methods you
    | define in the resources section.
    |
    */

    'policies' => [
        'path' => app_path('Policies'),
        'merge' => true,
        'generate' => true,
        'methods' => [
            'viewAny', 'view', 'create', 'update', 'delete', 'deleteAny', 'restore',
            'forceDelete', 'forceDeleteAny', 'restoreAny', 'replicate', 'reorder',
        ],
        'single_parameter_methods' => [
            'viewAny',
            'create',
            'deleteAny',
            'forceDeleteAny',
            'restoreAny',
            'reorder',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Localization
    |--------------------------------------------------------------------------
    |
    | Shield supports multiple languages out of the box. When enabled, you
    | can provide translated labels for permissions to create a more
    | localized experience for your international users.
    |
    */

    'localization' => [
        'enabled' => false,
        'key' => 'filament-shield::filament-shield.resource_permission_prefixes_labels',
    ],

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | Here you can fine-tune permissions for specific Filament resources.
    | Use the 'manage' array to override the default policy methods for
    | individual resources, giving you granular control over permissions.
    |
    */

    'resources' => [
        'subject' => 'model',
        'manage' => [
            RoleResource::class => [
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
            ],
        ],
        'exclude' => [
            //
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    |
    | Most Filament pages only require view permissions. Pages listed in the
    | exclude array will be skipped during permission generation and won't
    | appear in your role management interface.
    |
    */

    'pages' => [
        'subject' => 'class',
        'prefix' => 'view',
        'exclude' => [
            Dashboard::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Widgets
    |--------------------------------------------------------------------------
    |
    | Like pages, widgets typically only need view permissions. Add widgets
    | to the exclude array if you don't want them to appear in your role
    | management interface.
    |
    */

    'widgets' => [
        'subject' => 'class',
        'prefix' => 'view',
        'exclude' => [
            AccountWidget::class,
            FilamentInfoWidget::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Permissions
    |--------------------------------------------------------------------------
    |
    | Sometimes you need permissions that don't map to resources, pages, or
    | widgets. Define any custom permissions here and they'll be available
    | when editing roles in your application.
    |
    | Keys are formatted per the Permission Builder settings above; set
    | permissions.format_custom_permission_keys to false to use them as-is.
    |
    */

    'custom_permissions' => [
        'View:LogViewer' => 'View Log Viewer',
    ],

    /*
    |--------------------------------------------------------------------------
    | Entity Discovery
    |--------------------------------------------------------------------------
    |
    | By default, Shield only looks for entities in your default Filament
    | panel. Enable these options if you're using multiple panels and want
    | Shield to discover entities across all of them.
    |
    */

    'discovery' => [
        'discover_all_resources' => false,
        'discover_all_widgets' => false,
        'discover_all_pages' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Policy
    |--------------------------------------------------------------------------
    |
    | Shield can automatically register a policy for role management itself.
    | This lets you control who can manage roles using Laravel's built-in
    | authorization system. Requires a RolePolicy class in your app.
    |
    */

    'register_role_policy' => true,

];
