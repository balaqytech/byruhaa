# Store staging checklist

## Required environment

Set these values in staging; keep the values out of source control:

```text
APP_ENV=staging
APP_DEBUG=false
APP_URL=https://<staging-host>
SESSION_SECURE_COOKIE=true

THAWANI_MODE=test
THAWANI_TEST_SECRET_KEY=<Thawani test secret>
THAWANI_TEST_PUBLISHABLE_KEY=<Thawani test publishable key>
THAWANI_TEST_API_BASE_URL=https://uatcheckout.thawani.om/api/v1
THAWANI_TEST_CHECKOUT_BASE_URL=https://uatcheckout.thawani.om
THAWANI_WEBHOOK_TOKEN=<Thawani callback token>

UCHAT_STORE_API_TOKEN=<inbound UChat token>
UCHAT_STORE_OWNER_KEY_SECRET=<dedicated random secret>
UCHAT_STORE_WEBHOOK_URL=https://<uchat-webhook-endpoint>
UCHAT_STORE_WEBHOOK_BEARER_TOKEN=<outbound UChat token>
UCHAT_STORE_WEBHOOK_SIGNING_SECRET=<outbound signing secret>
UCHAT_STORE_RATE_LIMIT=60
QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=90
BYRUHAA_WEBHOOK_QUEUE=default
BYRUHAA_WEBHOOK_TIMEOUT=10
```

Do not set live Thawani keys or a live mode in staging. Missing Thawani test credentials must leave payment initiation unavailable. If UChat is not being enabled yet, leave its complete configuration unset; the rest of the site can boot normally.

## Deploy and verify

Run against the staging database only:

```text
php artisan migrate
php artisan db:seed --class=StoreCatalogSeeder
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan event:cache
```

Review every imported product manually: draft status, name/description, image, price, SKU, availability and stock. Confirm VAT/legal name, receipt address/phone/footer, opening hours and pickup instructions in Store settings. Keep ordering disabled until those reviews and the acceptance test pass; enable it as the final step.

Run one queue worker for the configured queue:

```text
php artisan queue:work database --queue=default --timeout=60 --tries=3
```

The database queue `retry_after` (90 seconds) must remain greater than the worker timeout (60 seconds), which must exceed the webhook request timeout/retry budget. Keep the worker running continuously. Run the scheduler continuously with:

```text
php artisan schedule:work
```

The scheduler runs `app:expire-store-orders` every minute, `seats:release-expired-holds` every minute, and `payments:reconcile-thawani` every five minutes. All three use `withoutOverlapping()`.

## Acceptance flow

1. Retrieve the catalog through UChat.
2. Create and update a cart.
3. Create an order with a stable idempotency key.
4. Open the Thawani test payment page.
5. Complete a test payment.
6. Confirm inventory is consumed once.
7. Change the order status through the staff flow.
8. Confirm UChat receives each signed webhook once and failed deliveries remain visible for retry.
9. Verify customer history and the VAT receipt.

After verification, keep generated caches in the normal staging deployment state. Never print or paste credentials into tickets, logs or test output.
