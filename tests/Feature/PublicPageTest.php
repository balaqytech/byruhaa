<?php

use App\Modules\Content\Enums\PublicPageStatus;
use App\Modules\Content\Models\PublicPage;
use App\Modules\Identity\Models\Customer;
use App\Modules\Identity\Models\FamilyMember;
use App\Modules\Identity\Models\MinorProfile;
use Database\Seeders\PublicPageSeeder;

test('published public pages are visible while drafts return not found', function (): void {
    $this->seed(PublicPageSeeder::class);

    $this->get(route('policies.show', ['page' => 'privacy']))
        ->assertOk()
        ->assertSee('سياسة الخصوصية');

    $this->get(route('policies.show', ['page' => 'faq']))
        ->assertNotFound();
});

test('public page publication scope excludes future and draft pages', function (): void {
    PublicPage::factory()->published()->create(['key' => 'published-page']);
    PublicPage::factory()->create(['key' => 'draft-page']);
    PublicPage::factory()->create([
        'key' => 'scheduled-page',
        'status' => PublicPageStatus::Published,
        'published_at' => now()->addDay(),
    ]);

    expect(PublicPage::query()->published()->pluck('key')->all())
        ->toBe(['published-page']);
});

test('sitemap contains published policy pages only', function (): void {
    $this->seed(PublicPageSeeder::class);

    $response = $this->get(route('sitemap'));

    $response->assertOk()
        ->assertSee(route('policies.show', ['page' => 'privacy']))
        ->assertSee(route('policies.show', ['page' => 'wallet']))
        ->assertSee(route('policies.show', ['page' => 'student-accounts']))
        ->assertDontSee(route('policies.show', ['page' => 'faq']));
});

test('wallet and minor policies are published and discoverable from the public footer', function (): void {
    $this->seed(PublicPageSeeder::class);

    $this->get(route('policies.show', ['page' => 'wallet']))
        ->assertOk()
        ->assertSee('شروط استخدام المحفظة')
        ->assertSee('الشحن لا يمنح الابن صلاحية الإنفاق تلقائيًا');

    $this->get(route('policies.show', ['page' => 'student-accounts']))
        ->assertOk()
        ->assertSee('لا توجد خطوة رمز تحقق منفصلة لإنشاء حساب القاصر');

    $this->get(route('home'))->assertOk()
        ->assertSee(route('policies.show', ['page' => 'wallet']), false)
        ->assertSee(route('policies.show', ['page' => 'student-accounts']), false);
});

test('refund policy uses the configured window without promising bank settlement within it', function (): void {
    config(['byruhaa.wallets.top_up_refund_window_hours' => 48]);
    $this->seed(PublicPageSeeder::class);

    $this->get(route('policies.show', ['page' => 'refund-cancellation']))
        ->assertOk()
        ->assertSee('48 ساعة من تأكيد دفع عملية الشحن')
        ->assertSee('مهلة طلب الاسترداد لا تعني ضمان وصوله خلال المدة نفسها')
        ->assertDontSee('{{refund_hours}}');
});

test('ordinary public page seeding preserves editorial changes and unrelated pages', function (): void {
    $privacy = PublicPage::factory()->published()->create(['key' => 'privacy', 'content' => '<p>محتوى معتمد من الإدارة</p>', 'version' => 7]);
    $custom = PublicPage::factory()->create(['key' => 'custom-page']);

    $this->seed(PublicPageSeeder::class);
    $this->seed(PublicPageSeeder::class);

    expect($privacy->refresh()->content)->toBe('<p>محتوى معتمد من الإدارة</p>')
        ->and($privacy->version)->toBe(7)
        ->and($custom->fresh())->not->toBeNull()
        ->and(PublicPage::query()->whereIn('key', PublicPage::FIXED_KEYS)->count())->toBe(count(PublicPage::FIXED_KEYS));
});

test('footer policy links have their own navigation column', function (): void {
    $this->seed(PublicPageSeeder::class);
    $html = $this->get(route('home'))->assertOk()->getContent();
    preg_match('/<nav\b[^>]*aria-label="روابط التذييل"[^>]*>(.*?)<\/nav>/s', $html, $siteNavigation);
    preg_match('/<nav\b[^>]*aria-label="السياسات والشروط"[^>]*>(.*?)<\/nav>/s', $html, $policyNavigation);

    expect($siteNavigation)->toHaveCount(2)
        ->and($policyNavigation)->toHaveCount(2)
        ->and($siteNavigation[1])->not->toContain('/policies/');

    foreach (['privacy', 'terms', 'pickup', 'refund-cancellation', 'student-accounts', 'wallet'] as $key) {
        expect($policyNavigation[1])->toContain(route('policies.show', ['page' => $key]));
    }
});

test('contextual policy links include only requested published pages and preserve form navigation', function (): void {
    $this->seed(PublicPageSeeder::class);
    PublicPage::query()->where('key', 'wallet')->update(['published_at' => now()->addDay()]);

    $this->blade('<x-policy-links :pages="[\'privacy\', \'wallet\', \'faq\']" />')
        ->assertSee(route('policies.show', ['page' => 'privacy']), false)
        ->assertSee('target="_blank"', false)
        ->assertSee('rel="noopener noreferrer"', false)
        ->assertDontSee(route('policies.show', ['page' => 'wallet']), false)
        ->assertDontSee(route('policies.show', ['page' => 'faq']), false)
        ->assertDontSee(route('policies.show', ['page' => 'terms']), false);
});

test('empty policy selections do not display a notice or navigation', function (): void {
    $this->blade('<x-policy-links :pages="[\'privacy\']" />')->assertDontSee('data-policy-links', false);
});

test('registration provides policy links inside the form', function (): void {
    $this->seed(PublicPageSeeder::class);
    $this->get(route('register'))->assertOk()->assertSeeInOrder([
        'راجع قبل إنشاء الحساب',
        route('policies.show', ['page' => 'privacy']),
        route('policies.show', ['page' => 'terms']),
        'data-test="register-user-button"',
    ], false);
});

test('guardian can read policies before creating a child account or funding its wallet', function (): void {
    config(['byruhaa.wallets.enabled' => true, 'byruhaa.minor_accounts.enabled' => true]);
    $this->seed(PublicPageSeeder::class);
    $guardian = Customer::factory()->create();
    $profile = MinorProfile::factory()->for(FamilyMember::factory()->for($guardian))->create();

    $this->actingAs($guardian, 'customer')->get(route('customer.minor-profiles.index'))
        ->assertOk()
        ->assertSee('راجع قبل إنشاء حساب الابن')
        ->assertSee('راجع قبل تغيير صلاحيات الدفع أو طلب الحذف')
        ->assertSee(route('policies.show', ['page' => 'student-accounts']), false)
        ->assertSee(route('policies.show', ['page' => 'wallet']), false);

    $this->get(route('customer.minor-profiles.wallet', $profile))->assertOk()->assertSeeInOrder([
        'راجع شروط الشحن والاسترداد قبل الدفع',
        route('policies.show', ['page' => 'refund-cancellation']),
        route('policies.show', ['page' => 'wallet']),
        'متابعة الدفع عبر ثواني',
    ], false);
});
