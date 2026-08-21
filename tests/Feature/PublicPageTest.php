<?php

use App\Modules\Content\Enums\PublicPageStatus;
use App\Modules\Content\Models\PublicPage;
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
        ->assertDontSee(route('policies.show', ['page' => 'faq']));
});
