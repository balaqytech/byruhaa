<?php

return [
    'approval_mechanism' => env('BYRUHAA_APPROVAL_MECHANISM', 'manual'),
    'seat_hold_minutes' => (int) env('BYRUHAA_SEAT_HOLD_MINUTES', 15),

    'minor_accounts' => [
        'enabled' => filter_var(env('MINOR_ACCOUNTS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
        'otp_expiry_minutes' => (int) env('MINOR_ACCOUNT_OTP_EXPIRY_MINUTES', 10),
        'policy_version' => env('MINOR_ACCOUNT_POLICY_VERSION', 'phase-2a'),
        'policy_text' => env('MINOR_ACCOUNT_POLICY_TEXT', 'guardian-consent-store-purchase'),
    ],

    'uchat' => [
        'api_token' => env('UCHAT_STORE_API_TOKEN'),
        'webhook_url' => env('UCHAT_STORE_WEBHOOK_URL'),
        'webhook_bearer_token' => env('UCHAT_STORE_WEBHOOK_BEARER_TOKEN'),
        'webhook_signing_secret' => env('UCHAT_STORE_WEBHOOK_SIGNING_SECRET'),
        'owner_key_secret' => env('UCHAT_STORE_OWNER_KEY_SECRET'),
        'rate_limit' => (int) env('UCHAT_STORE_RATE_LIMIT', 60),
    ],

    'webhooks' => [
        'customer_registered_url' => env('BYRUHAA_CUSTOMER_REGISTERED_WEBHOOK_URL'),
        'interest_created_url' => env('BYRUHAA_INTEREST_CREATED_WEBHOOK_URL'),
        'booking_created_url' => env('BYRUHAA_BOOKING_CREATED_WEBHOOK_URL'),
        'booking_approved_url' => env('BYRUHAA_BOOKING_APPROVED_WEBHOOK_URL'),
        'booking_cancelled_url' => env('BYRUHAA_BOOKING_CANCELLED_WEBHOOK_URL'),
        'booking_contracts_signed_url' => env('BYRUHAA_BOOKING_CONTRACTS_SIGNED_WEBHOOK_URL'),
        'payment_paid_url' => env('BYRUHAA_PAYMENT_PAID_WEBHOOK_URL'),
        'event_cancelled_url' => env('BYRUHAA_EVENT_CANCELLED_WEBHOOK_URL'),
        'payment_refunded_url' => env('BYRUHAA_PAYMENT_REFUNDED_WEBHOOK_URL'),
        'signing_secret' => env('BYRUHAA_WEBHOOK_SIGNING_SECRET'),
        'timeout' => env('BYRUHAA_WEBHOOK_TIMEOUT', 10),
        'queue' => env('BYRUHAA_WEBHOOK_QUEUE', 'default'),
    ],
];
