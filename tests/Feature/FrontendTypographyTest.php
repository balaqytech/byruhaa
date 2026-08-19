<?php

test('frontend typography uses thmanyah headings and text', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain("font-family: 'Thmanyah Sans'")
        ->toContain("font-family: 'Thmanyah Serif Display'")
        ->toContain("--font-sans: 'Thmanyah Sans'")
        ->toContain("--font-heading: 'Thmanyah Serif Display'")
        ->toContain('font-family: var(--font-sans)')
        ->toContain('font-family: var(--font-heading)')
        ->toContain("font-feature-settings: 'swsh' 1")
        ->toContain('font-weight: 700')
        ->toContain('font-weight: 900')
        ->toContain('font-size: 1.125em')
        ->toContain('font-size: 2rem');

    expect(file_get_contents(base_path('vite.config.js')))
        ->not->toContain("bunny('Reem Kufi'")
        ->not->toContain("bunny('IBM Plex Sans Arabic'");
});
