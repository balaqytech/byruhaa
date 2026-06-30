<?php

$env = static function (string $key, mixed $default = null): mixed {
    $value = env($key);

    return $value === null || $value === '' ? $default : $value;
};

$checkoutBaseUrl = static function (mixed $value): string {
    $url = rtrim((string) $value, '/');

    return str_ends_with($url, '/pay') ? $url : $url.'/pay';
};

$testCheckoutBaseUrl = $checkoutBaseUrl($env('THAWANI_TEST_CHECKOUT_BASE_URL', $env('THAWANI_CHECKOUT_BASE_URL', 'https://uatcheckout.thawani.om')));
$liveCheckoutBaseUrl = $checkoutBaseUrl($env('THAWANI_LIVE_CHECKOUT_BASE_URL', $env('THAWANI_CHECKOUT_BASE_URL', 'https://checkout.thawani.om')));

return [
    'mode' => $env('THAWANI_MODE', 'test'),

    'webhook' => [
        'token' => $env('THAWANI_WEBHOOK_TOKEN'),
    ],

    'test' => [
        'base_url' => $env('THAWANI_TEST_API_BASE_URL', $env('THAWANI_API_BASE_URL', 'https://uatcheckout.thawani.om/api/v1')),
        'checkout_base_url' => $testCheckoutBaseUrl,
        'secret_key' => $env('THAWANI_TEST_SECRET_KEY', $env('THAWANI_SECRET_KEY', 'rRQ26GcsZzoEhbrP2HZvLYDbn9C9et')),
        'publishable_key' => $env('THAWANI_TEST_PUBLISHABLE_KEY', $env('THAWANI_PUBLISHABLE_KEY', 'HGvTMLDssJghr9tlN9gr4DVYt0qyBy')),
    ],

    'live' => [
        'base_url' => $env('THAWANI_LIVE_API_BASE_URL', $env('THAWANI_API_BASE_URL', 'https://checkout.thawani.om/api/v1')),
        'checkout_base_url' => $liveCheckoutBaseUrl,
        'secret_key' => $env('THAWANI_LIVE_SECRET_KEY', $env('THAWANI_SECRET_KEY')),
        'publishable_key' => $env('THAWANI_LIVE_PUBLISHABLE_KEY', $env('THAWANI_PUBLISHABLE_KEY')),
    ],
];
