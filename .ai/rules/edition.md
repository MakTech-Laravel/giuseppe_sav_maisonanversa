---
paths:
  - 'app/Services/Edition/**'
---

# Edition

## Edition hold starts on picker select
A logged-in customer clicking a limited-edition number holds that piece for config maison.checkout.hold_minutes (15). Only one reserved piece per user per product: switching numbers releases the previous hold. If they never pay, editions:release-expired-holds (every minute) returns it to available and expires any Stripe Checkout session. Listing or holding editions, and starting checkout, also sweep expired holds so a number unlocks without waiting for cron. Pay attaches that existing hold to the new order; do not fulfill a canceled expired-hold order onto a different number.

## Repair stuck Sold editions after canceled payment
After deploy, if a Canceled order still holds an Allocated piece (picker Sold / register Private member), do not mass-rewrite paid orders. Prefer Ops “remove circle member” when the place was Ops-assigned. For a canceled commerce order, run the same unwind once in tinker: registrar->releaseForOrder($order) then allocator->releaseOnCancel($order) for that order only (match by order number or edition_piece_id). Re-check piece status Available and register row gone.
