<?php

use App\Modules\Affiliates\Models\Affiliate;
use App\Modules\Affiliates\Models\AffiliateCommission;
use App\Modules\Affiliates\Models\AffiliatePayoutRequest;
use App\Modules\Affiliates\Models\AffiliateReferral;
use App\Modules\Content\Models\BlogPost;
use App\Modules\Content\Models\BlogPostCategory;
use App\Modules\Events\Models\Booking;
use App\Modules\Events\Models\Event;
use App\Modules\Finance\Models\Payment;
use App\Modules\Identity\Models\Customer;
use App\Modules\Store\Providers\StoreServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

test('domain models live under their owning module namespaces', function (): void {
    foreach ([
        Affiliate::class,
        AffiliateCommission::class,
        AffiliatePayoutRequest::class,
        AffiliateReferral::class,
        BlogPost::class,
        BlogPostCategory::class,
        Booking::class,
        Event::class,
        Payment::class,
        Customer::class,
    ] as $model) {
        expect(str_starts_with($model, 'App\\Modules\\'))->toBeTrue();
    }
});

test('legacy model paths are no longer present after migration', function (): void {
    foreach ([
        'app/Models/Affiliate.php',
        'app/Models/AffiliateCommission.php',
        'app/Models/AffiliatePayoutRequest.php',
        'app/Models/AffiliateReferral.php',
        'app/Models/BlogPost.php',
        'app/Models/BlogPostCategory.php',
        'app/Models/Booking.php',
        'app/Models/Event.php',
        'app/Models/Payment.php',
        'app/Models/Customer.php',
    ] as $path) {
        expect(is_file(base_path($path)))->toBeFalse();
    }
});

test('module providers preserve legacy polymorphic model aliases', function (): void {
    expect(Relation::getMorphedModel('App\\Models\\Customer'))->toBe(Customer::class)
        ->and(Relation::getMorphedModel('App\\Models\\Booking'))->toBe(Booking::class)
        ->and(Relation::getMorphedModel('App\\Models\\Payment'))->toBe(Payment::class)
        ->and(Relation::getMorphedModel('App\\Models\\AffiliateCommission'))->toBe(AffiliateCommission::class)
        ->and(Relation::getMorphedModel('App\\Models\\AffiliatePayoutRequest'))->toBe(AffiliatePayoutRequest::class);
});

test('moved modules do not import their former root namespaces', function (): void {
    $patterns = [
        'App\\Models\\Affiliate',
        'App\\Models\\BlogPost',
        'App\\Models\\Booking',
        'App\\Models\\Event',
        'App\\Models\\Payment',
        'App\\Models\\Customer',
        'App\\Actions\\CreateAffiliatePayoutRequest',
        'App\\Actions\\PostAffiliate',
        'App\\Services\\AffiliateAttribution',
    ];

    foreach (glob(base_path('app/Modules/*/*/*.php')) ?: [] as $path) {
        $contents = file_get_contents($path);

        foreach ($patterns as $pattern) {
            expect($contents)->not->toContain("use {$pattern}");
        }
    }
});

test('future commerce module is registered as an explicit application provider', function (): void {
    $providers = require base_path('bootstrap/providers.php');

    expect($providers)
        ->toContain(StoreServiceProvider::class);
});
