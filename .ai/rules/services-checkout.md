---
paths:
  - 'app/Services/Checkout/**'
---

# Services Checkout

## Founding checkout does not create a claim
OrderFulfillment inscribes Heritage No.001 buyers through FoundingCircleRegistrar after the edition piece is allocated. Other products never grant founding-circle, even when grants_founding_circle is true. markRefunded releases the register row before inventory so the edition number still matches.
