---
paths:
  - 'app/Services/FoundingCircle/**'
---

# Founding Circle

## Heritage payment creates Reserved, then Inscribed after the window
Only Product::FOUNDING_SLUG (heritage-no-001) creates Founding Circle membership. Successful payment writes founding_circle_register with `inscribed_at` null (public ledger state `reserved` / Gereserveerd, no name) and grants the role. Daily `founding-circle:promote-inscriptions` sets `inscribed_at` when delivery + withdrawal_days has passed and queues InscriptionMail. Until then Manage listing is blocked. Admin assign sets `inscribed_at` immediately. A refund deletes the row and drops the role. Public ledger rows: reserved, returning, inscribed (name/Privélid), Niet te koop, Beschikbaar. Names come from the live account after inscription.

## Return requests mark the piece Returning
When a customer requests a return for a Heritage racket, FoundingCircleRegistrar::markReturningForOrder sets edition_pieces.status to Returning while the register row stays. The public ledger shows Gereserveerd (state returning). Only a full refund calls releaseForOrder (Available + name gone). Partial refund keeps the number and calls restoreAllocatedForOrder. Declined or cancelled returns also call restoreAllocatedForOrder; the place stays Reserved or Inscribed according to whether `inscribed_at` was already set.
