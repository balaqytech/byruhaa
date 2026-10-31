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

UCHAT_API_TOKEN=<inbound UChat token>
UCHAT_STORE_OWNER_KEY_SECRET=<dedicated random secret>
UCHAT_WEBHOOK_URL=https://<uchat-webhook-endpoint>
UCHAT_WEBHOOK_BEARER_TOKEN=<outbound UChat token>
UCHAT_WEBHOOK_SIGNING_SECRET=<outbound signing secret>
UCHAT_RATE_LIMIT=60
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

## Unified UChat cutover — 2026-10-29

This is a breaking API deployment. Code preparation does not confirm staging deployment.

1. Back up the database and inventory pending/queued webhook deliveries before deployment. Pause the scheduler and gracefully drain the old webhook queue before switching the destination and receiver contract; do not discard queued jobs or rotate APP_KEY.
2. Copy the existing UCHAT_STORE_API_TOKEN value into UCHAT_API_TOKEN. Keep UCHAT_STORE_OWNER_KEY_SECRET unchanged so existing phone-owned carts retain their owner keys. Rename UCHAT_STORE_RATE_LIMIT to UCHAT_RATE_LIMIT.
3. Configure one UCHAT_WEBHOOK_URL, UCHAT_WEBHOOK_BEARER_TOKEN and UCHAT_WEBHOOK_SIGNING_SECRET. Move the existing Store webhook credentials to those names if retaining that receiver. The old per-event BYRUHAA_*_WEBHOOK_URL and BYRUHAA_WEBHOOK_SIGNING_SECRET settings no longer control outgoing events.
4. Update both UChat assistants to /api/v1/integrations/uchat, send the trusted X-WhatsApp-Phone on every request, and route incoming messages by event. Remove customer IDs and phone_number identity fields from payloads. Use booking/payment references in detail paths.
5. Run the new migrations and rebuild configuration/routes. Restart Queue workers after code deployment, then resume the scheduler. Leave retained legacy deliveries untouched; only encrypted dispatch_payload snapshots produced by this release are eligible for recovery.
6. Verify a correct token plus phone can read account and event-interests; incorrect tokens return 401, missing phones return 422, and another customer's resources return 404. Confirm the old /customers and /integrations/assistant paths return 404.
7. Submit one Booking twice with the same Idempotency-Key and verify one booking, one coupon redemption and one booking.created delivery. Change the request with that key and verify 409.
8. Cancel a test Event and confirm event.cancelled, a booking.cancelled with each affected phone/reference, and a completion/needs_attention event. Verify signature against raw JSON and deduplicate by delivery_id.
9. Confirm all shared configuration is complete, the receiver uses HTTPS, failed deliveries are observable, and uchat:webhooks:dispatch-pending runs every minute. Retry selected failed delivery IDs with uchat:webhooks:retry; retries preserve delivery_id and snapshot.
10. Confirm Store guest ordering, minor ownership, Wallet top-up and VAT quotes still work. Do not treat a checkout URL or API response as payment confirmation.

On rollback, restore the matching application and assistant/receiver configuration together. Keep the additive migrations and stored snapshots; do not roll them back after new bookings or deletion requests have been created. Maintain the same APP_KEY to decrypt recoverable webhook payloads.
