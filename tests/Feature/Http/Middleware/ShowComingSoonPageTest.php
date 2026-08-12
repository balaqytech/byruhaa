<?php

use App\Settings\GeneralSettings;

test('public website remains available when coming soon mode is disabled', function () {
    expect(app(GeneralSettings::class)->coming_soon_enabled)->toBeFalse();

    $this->get(route('home'))->assertSuccessful();
});

test('coming soon mode closes web pages but keeps admin api and health routes available', function () {
    $settings = app(GeneralSettings::class);
    $settings->coming_soon_enabled = true;
    $settings->save();

    foreach ([route('home'), route('events.index'), route('about'), route('login'), route('affiliate.login')] as $url) {
        $this->get($url)
            ->assertServiceUnavailable()
            ->assertHeader('Retry-After', '3600')
            ->assertSee('الموقع قريبًا')
            ->assertSee('noindex, nofollow', false);
    }

    $this->get('/admin/login')->assertSuccessful();
    $this->getJson('/api/v1/events')->assertSuccessful();
    $this->get('/up')->assertSuccessful();
});

test('disabling coming soon mode restores the public website', function () {
    $settings = app(GeneralSettings::class);
    $settings->coming_soon_enabled = true;
    $settings->save();

    $this->get(route('home'))->assertServiceUnavailable();

    $settings->coming_soon_enabled = false;
    $settings->save();

    $this->get(route('home'))->assertSuccessful();
});
