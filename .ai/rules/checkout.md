---
paths:
  - 'app/Services/FoundingCircle/**,app/Support/PassportPresenter.php,app/Services/Checkout/OrderFulfillment.php'
---

# Checkout

## Founding Circle requires claim approval
Checkout for grants_founding_circle products only allocates the unique edition piece and creates a pending FoundingCircleClaim. Do not assign the founding-circle role or write founding_circle_register on pay. Admin approve (FoundingCircleClaimService::approve) grants the role + naamregister; reject frees the serial for re-claim. Admin circle.assign remains an ops override. Passport/Circle stay presenter composites gated by the role.
