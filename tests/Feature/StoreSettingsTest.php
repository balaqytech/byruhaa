<?php

use App\Modules\Identity\Models\User;
use App\Modules\Store\Filament\Pages\ManageStoreSettings;
use App\Modules\Store\Settings\StoreSettings;
use Livewire\Livewire;

test('store settings default to five percent VAT and two minute reservations', function (): void {
    $settings = app(StoreSettings::class);

    expect($settings->vat_rate_percentage)->toBe(5)
        ->and($settings->reservation_duration_minutes)->toBe(2)
        ->and($settings->ordering_enabled)->toBeFalse();
});

test('staff can update store settings from filament', function (): void {
    $this->actingAs(User::factory()->create(), 'web');

    Livewire::test(ManageStoreSettings::class)
        ->fillForm([
            'vat_rate_percentage' => 5,
            'reservation_duration_minutes' => 3,
            'ordering_enabled' => true,
            'legal_name' => 'Byruhaa',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(app(StoreSettings::class)->refresh())
        ->vat_rate_percentage->toBe(5)
        ->reservation_duration_minutes->toBe(3)
        ->ordering_enabled->toBeTrue()
        ->legal_name->toBe('Byruhaa');
});
