# Stripe production webhook

## Endpoint

```
https://maisonanversa.com/stripe/webhook
```

- No locale prefix (`/en`, `/nl`, `/fr` must **not** be in the path)
- Method: `POST`
- Payload type: **Snapshot** (not Thin)
- Mode: **Live** for production

After creating the destination in Stripe, put the signing secret (`whsec_…`) in production env as:

```
STRIPE_WEBHOOK_SECRET=whsec_...
```

Then clear config cache / redeploy so Laravel loads it.

---

## Events to select

Uncheck **Select all**. Enable only these **13** events.

Open this file in the Markdown preview, then use the copy icon on each code block.

### Checkout / one-time payment

```
checkout.session.completed
```

```
checkout.session.async_payment_succeeded
```

```
checkout.session.async_payment_failed
```

```
checkout.session.expired
```

```
charge.refunded
```

### Cashier defaults

```
customer.subscription.created
```

```
customer.subscription.updated
```

```
customer.subscription.deleted
```

```
customer.updated
```

```
customer.deleted
```

```
payment_method.automatically_updated
```

```
invoice.payment_action_required
```

```
invoice.payment_succeeded
```

---

## What each payment event does in this app

| Event | Purpose |
|--------|---------|
| `checkout.session.completed` | Mark order paid when `payment_status` is `paid` (card) |
| `checkout.session.async_payment_succeeded` | Mark order paid for async methods (e.g. Bancontact) |
| `checkout.session.async_payment_failed` | Mark payment/order failed |
| `checkout.session.expired` | Cancel incomplete checkout / release holds |
| `charge.refunded` | Mark order refunded and restore inventory |

Source of truth: `config/cashier.php` (`cashier.webhook.events`).
