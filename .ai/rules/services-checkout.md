---
paths:
  - 'app/Services/Checkout/**'
---

# Services Checkout

## Founding checkout does not create a claim
OrderFulfillment inscribes Heritage No.001 buyers through FoundingCircleRegistrar after the edition piece is allocated. Other products never grant founding-circle, even when grants_founding_circle is true. markRefunded releases the register row before inventory so the edition number still matches.

## Paid orders upsert Brevo list 2
On first successful payment only, OrderFulfillment dispatches SyncOrderToBrevo. That upserts the logged-in `users.email` (not `orders.email`), name, phone, and an order note onto BREVO_LIST_ORDERS (default 2). Do not write newsletter_subscribers or put buyers on the Heritage Letter list.

## Checkout success does not fulfill
Payment is confirmed only by the Stripe webhook (`checkout.session.completed` / `async_payment_succeeded`). The success page reads the local order by session_id and polls until it is paid. Do not retrieve the Stripe session or call markPaidFromSession from CheckoutController::success.

## Stripe webhook URL has no locale
Payment webhooks hit POST /stripe/webhook with no /en|/nl|/fr prefix. Do not point Stripe Dashboard or stripe listen at /{locale}/stripe/webhook. CSRF exempts stripe/*; fulfillment still comes only from WebhookReceived.

## Clear missing Stripe customer before checkout
Logged-in checkout uses Cashier Checkout::customer($user). If users.stripe_id points at a deleted or other-account customer, Stripe throws No such customer. Call StripeCustomerGuard::forgetIfMissing($user) before creating the session so Cashier can create a fresh customer.
