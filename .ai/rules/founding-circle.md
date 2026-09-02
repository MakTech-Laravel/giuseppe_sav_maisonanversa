---
paths:
  - 'app/Services/FoundingCircle/**'
---

# Founding Circle

## Founding Circle register is append-only
FoundingCircleRegistrar::register() firstOrCreates one founding_circle_register row per user_id. Snapshot name, edition, and joined_at at write time and never update or delete the row on role removal, refunds, or profile changes. Admin Circle → Naamregister is the ledger UI.
