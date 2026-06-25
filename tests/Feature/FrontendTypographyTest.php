<?php

test('frontend typography uses reem kufi headings and ibm plex text', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain("--font-sans: 'IBM Plex Sans Arabic'")
        ->toContain("--font-heading: 'Reem Kufi'")
        ->toContain('font-family: var(--font-sans)')
        ->toContain('font-family: var(--font-heading)')
        ->toContain('font-size: 1.125em')
        ->toContain('font-size: 2rem');

    expect(file_get_contents(base_path('vite.config.js')))
        ->toContain("bunny('Reem Kufi'")
        ->not->toContain("bunny('Aref Ruqaa'");
});
