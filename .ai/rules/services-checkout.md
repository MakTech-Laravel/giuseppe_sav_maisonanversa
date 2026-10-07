---
paths:
  - 'app/Services/Checkout/**'
---

# Services Checkout

## Founding checkout does not create a claim
OrderFulfillment inscribes Heritage No.001 buyers through FoundingCircleRegistrar after the edition piece is allocated. Other products never grant founding-circle, even when grants_founding_circle is true. markRefunded releases the register row before inventory so the edition number still matches.

## Paid orders upsert Brevo list 2
On first successful payment only, OrderFulfillment dispatches SyncOrderToBrevo. That upserts the logged-in `users.email` (not `orders.email`), name, phone, and an order note onto BREVO_LIST_ORDERS (default 2). Do not write newsletter_subscribers or put buyers on the Heritage Letter list.

## Checkout success confirms with Stripe
Payment is confirmed by the Stripe webhook (`checkout.session.completed` / `async_payment_succeeded`). If that event has not arrived, CheckoutController::success calls CheckoutSessionReconciler, which retrieves the Checkout Session with the secret key and calls markPaidFromSession only when payment_status is paid and metadata order_id and payment_id match the local order. Do not mark paid from the redirect by itself.

## Stripe webhook URL has no locale
Payment webhooks hit POST /stripe/webhook with no /en|/nl|/fr prefix. Do not point Stripe Dashboard or stripe listen at /{locale}/stripe/webhook. CSRF exempts stripe/*. Local listen must use --all-snapshot, not --all-thin. Webhook fulfillment still comes from WebhookReceived; the success page only reconciles after a server-side Stripe retrieve.

## Clear missing Stripe customer before checkout
Logged-in checkout uses Cashier Checkout::customer($user). If users.stripe_id points at a deleted or other-account customer, Stripe throws No such customer. Call StripeCustomerGuard::forgetIfMissing($user) before creating the session so Cashier can create a fresh customer.

## Incomplete cancel frees Allocated pieces and register
markCanceledBySessionId, markFailedFromSession, and cancelIncomplete must call registrar->releaseForOrder then allocator->releaseOnCancel (not release). release() leaves Allocated alone; releaseOnCancel clears Reserved and Allocated the same way releaseOnRefund does. Paid orders still unwind only via markRefunded.
