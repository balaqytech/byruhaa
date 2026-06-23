<?php

return [
    'approval_mechanism' => env('BYRUHAA_APPROVAL_MECHANISM', 'manual'),

    'webhooks' => [
        'customer_registered_url' => env('BYRUHAA_CUSTOMER_REGISTERED_WEBHOOK_URL'),
        'booking_created_url' => env('BYRUHAA_BOOKING_CREATED_WEBHOOK_URL'),
        'booking_approved_url' => env('BYRUHAA_BOOKING_APPROVED_WEBHOOK_URL'),
        'booking_contracts_signed_url' => env('BYRUHAA_BOOKING_CONTRACTS_SIGNED_WEBHOOK_URL'),
        'payment_paid_url' => env('BYRUHAA_PAYMENT_PAID_WEBHOOK_URL'),
        'signing_secret' => env('BYRUHAA_WEBHOOK_SIGNING_SECRET'),
        'timeout' => env('BYRUHAA_WEBHOOK_TIMEOUT', 10),
        'queue' => env('BYRUHAA_WEBHOOK_QUEUE', 'default'),
    ],
];
