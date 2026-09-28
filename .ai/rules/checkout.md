---
paths:
  - 'app/Services/FoundingCircle/**,app/Support/PassportPresenter.php,app/Services/Checkout/OrderFulfillment.php'
---

# Checkout

## Heritage No.001 payment inscribes the register
OrderFulfillment inscribes Heritage No.001 buyers through FoundingCircleRegistrar after the edition piece is allocated. Other products never grant founding-circle, even when grants_founding_circle is true. markRefunded releases the register row before inventory so the edition number still matches. Passport and the Circle card stay presenter composites gated by the role.

## Paid orders upsert Brevo list 2
On first successful payment only, OrderFulfillment dispatches SyncOrderToBrevo. That upserts the logged-in `users.email` (not `orders.email`), name, phone, and an order note onto BREVO_LIST_ORDERS (default 2). Do not write newsletter_subscribers or put buyers on the Heritage Letter list.
