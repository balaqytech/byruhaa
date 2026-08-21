# UChat Store Integration

The Store assistant API is versioned under `/api/v1/integrations/uchat/store`.

## Authentication

Every request requires:

```text
Authorization: Bearer ${UCHAT_STORE_API_TOKEN}
X-WhatsApp-Phone: 91234567
```

The phone header is normalized by Identity and is the only customer identity accepted by this integration. A matching customer is associated automatically; otherwise the cart and order remain a guest flow. The API token is never returned or logged.

The integration fails closed unless both `UCHAT_STORE_API_TOKEN` and `UCHAT_STORE_OWNER_KEY_SECRET` are set. The owner-key secret is dedicated to UChat and is never derived from `APP_KEY`. Requests are throttled per source IP and integration fingerprint; changing the WhatsApp phone header does not bypass the limit.

## Endpoints

| Method | Path | Purpose |
| --- | --- | --- |
| GET | `/catalog` | Published catalog, options, prices and availability |
| GET | `/products/{slug}` | One published product |
| GET | `/cart` | Current phone-owned cart and server quote |
| POST | `/cart/items` | Add by SKU (`sku`, `quantity`, optional `note`) |
| PATCH | `/cart/items/{sku}` | Update quantity or note |
| DELETE | `/cart/items/{sku}` | Remove an item |
| POST | `/orders` | Create an order and initiate Thawani payment |
| POST | `/orders/{reference}/payment` | Retry an eligible payment |
| GET | `/orders` | Phone-owned paginated history |
| GET | `/orders/{reference}` | Phone-owned order details |

Order creation requires `Idempotency-Key`. Repeating the same key for the same normalized phone returns the same order; reusing it for another phone is rejected. Prices, VAT, totals, currency, availability and ownership are always calculated on the server.

All monetary fields ending in `_baisa` are integer values. Product `price_baisa` and order-item `unit_price_baisa`/`line_total_baisa` are final VAT-inclusive prices. `subtotal_baisa` and `line_subtotal_baisa` are the amounts before VAT, while `vat_baisa` is the embedded 5% VAT amount. The invariant is `total_baisa = subtotal_baisa + vat_baisa`; this final total is the amount sent to Thawani (there is no separate VAT line item). For example, a VAT-inclusive price of 2,000 baisa is represented as 1,905 baisa before VAT plus 95 baisa VAT.

Example order request:

```json
{
  "customer_name": "Mona Said",
  "pickup_type": "immediate",
  "note": "No sugar"
}
```

Successful order responses contain `data.reference`, `data.status`, immutable item snapshots and a `payment` object with `status`, `checkout_url` and `expires_at`.

## Error codes

`uchat_unauthorized`, `validation_failed`, `product_unavailable`, `cart_item_not_found`, and `order_not_found` are stable machine-readable codes. Validation responses include an `errors` object. Rate limiting returns the normal Laravel `429` response.

## Outbound webhooks

When configured, each real Store order transition is sent after commit with one delivery per transition:

`store.order.confirmed`, `accepted`, `preparing`, `ready_for_pickup`, `completed`, `rejected`, `cancelled`, `expired`, `refund_pending`, and `refunded`.

Webhook requests use the configured Bearer header and Spatie signing. The payload includes `event`, `delivery_id`, `occurred_at`, `customer_phone`, an order snapshot under `data.order`, and secure status/payment links under `data.links`. Internal IDs, cart tokens, provider payloads and secrets are not exposed.

If a webhook URL is set, all three outbound values are required: URL, Bearer token and signing secret. In staging and production the URL must use HTTPS. An incomplete or insecure configuration is logged as a disabled integration and no unsigned request is queued.

## Environment

```text
UCHAT_STORE_API_TOKEN=
UCHAT_STORE_WEBHOOK_URL=
UCHAT_STORE_WEBHOOK_BEARER_TOKEN=
UCHAT_STORE_WEBHOOK_SIGNING_SECRET=
UCHAT_STORE_OWNER_KEY_SECRET=
UCHAT_STORE_RATE_LIMIT=60
```

UChat carts created before the dedicated owner-key secret was configured are not migrated. Since the integration has not launched, they may be abandoned and recreated after staging configuration.
