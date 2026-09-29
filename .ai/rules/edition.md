---
paths:
  - 'app/Services/Edition/**'
---

# Edition

## Edition hold starts on picker select
A logged-in customer clicking a limited-edition number holds that piece for config maison.checkout.hold_minutes (15). Only one reserved piece per user per product: switching numbers releases the previous hold. If they never pay, editions:release-expired-holds (every minute) returns it to available and expires any Stripe Checkout session. Listing or holding editions, and starting checkout, also sweep expired holds so a number unlocks without waiting for cron. Pay attaches that existing hold to the new order; do not fulfill a canceled expired-hold order onto a different number.
