<?php

test('frontend typography uses ruqaa headings and ibm plex text', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain("--font-sans: 'IBM Plex Sans Arabic'")
        ->toContain("--font-heading: 'Aref Ruqaa'")
        ->toContain('font-family: var(--font-sans)')
        ->toContain('font-family: var(--font-heading)');
});
