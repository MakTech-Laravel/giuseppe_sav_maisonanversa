---
paths:
  - 'app/Services/FoundingCircle/**'
---

# Founding Circle

## Founding Circle register is append-only
FoundingCircleRegistrar::register() firstOrCreates one founding_circle_register row per user_id. Snapshot name, edition, and joined_at at write time and never update or delete the row on role removal, refunds, or profile changes. Admin Circle → Naamregister is the ledger UI.

## Enrollment requires claim approval
Checkout for `grants_founding_circle` products only allocates the unique edition piece and creates a pending `FoundingCircleClaim`. Do not assign the `founding-circle` role or write `founding_circle_register` on pay. Admin approve (`FoundingCircleClaimService::approve`) grants the role + naamregister; reject frees the serial for re-claim. Refunds auto-reject pending order-sourced claims. `admin.circle.assign` remains an ops override that grants role + register immediately.
