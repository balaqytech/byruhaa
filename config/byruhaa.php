<?php

return [
    'webhooks' => [
        'booking_approved_url' => env('BYRUHAA_BOOKING_APPROVED_WEBHOOK_URL'),
        'payment_paid_url' => env('BYRUHAA_PAYMENT_PAID_WEBHOOK_URL'),
        'signing_secret' => env('BYRUHAA_WEBHOOK_SIGNING_SECRET'),
        'timeout' => env('BYRUHAA_WEBHOOK_TIMEOUT', 10),
        'queue' => env('BYRUHAA_WEBHOOK_QUEUE', 'default'),
    ],
];
