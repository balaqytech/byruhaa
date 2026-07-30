<?php

use App\Filament\Pages\ManageAboutPage;
use App\Filament\Pages\ManageContactPage;
use App\Models\User;
use App\Settings\AboutPageSettings;
use App\Settings\ContactPageSettings;
use Livewire\Livewire;

test('staff can update the about and contact pages from filament', function () {
    $this->actingAs(User::factory()->create(), 'web');

    Livewire::test(ManageAboutPage::class)
        ->assertFormSet([
            'page_title' => 'مخيم بيرحاء إبراء',
        ])
        ->fillForm([
            'page_title' => 'بيرحاء كما نعيشها',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    Livewire::test(ManageContactPage::class)
        ->assertFormSet([
            'phone' => '+968 7415 5123',
        ])
        ->fillForm([
            'phone' => '+968 9000 0000',
            'social_links' => [
                ['platform' => 'instagram', 'url' => 'https://instagram.com/byruhaa'],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(app(AboutPageSettings::class)->refresh()->page_title)->toBe('بيرحاء كما نعيشها')
        ->and(app(ContactPageSettings::class)->refresh()->phone)->toBe('+968 9000 0000');

    $this->get(route('about'))
        ->assertSuccessful()
        ->assertSee('بيرحاء كما نعيشها');

    $this->get(route('contact'))
        ->assertSuccessful()
        ->assertSee('+968 9000 0000')
        ->assertSee('إنستغرام')
        ->assertSee('https://instagram.com/byruhaa', false);
});

test('about page content cannot exceed five hundred words', function () {
    $this->actingAs(User::factory()->create(), 'web');

    Livewire::test(ManageAboutPage::class)
        ->fillForm([
            'intro_body' => implode(' ', array_fill(0, 501, 'كلمة')),
        ])
        ->call('save')
        ->assertHasFormErrors(['intro_body']);
});

test('contact settings reject unsafe links and public pages tolerate missing media', function () {
    $this->actingAs(User::factory()->create(), 'web');

    Livewire::test(ManageContactPage::class)
        ->fillForm([
            'assistant_url' => 'javascript:alert(1)',
            'map_url' => 'file:///etc/passwd',
            'social_links' => [
                ['platform' => 'instagram', 'url' => 'javascript:alert(1)'],
            ],
        ])
        ->call('save')
        ->assertHasFormErrors([
            'assistant_url',
            'map_url',
            'social_links.0.url',
        ]);

    $about = app(AboutPageSettings::class);
    $about->hero_image_id = '999999';
    $about->gallery_image_ids = ['999998', '999999'];
    $about->save();

    $this->get(route('about'))
        ->assertSuccessful()
        ->assertSee('مخيم بيرحاء إبراء')
        ->assertDontSee('999999');
});

test('guests are redirected from the site settings pages', function () {
    $this->get(ManageAboutPage::getUrl(panel: 'admin'))
        ->assertRedirect('/admin/login');

    $this->get(ManageContactPage::getUrl(panel: 'admin'))
        ->assertRedirect('/admin/login');
});
