---
paths:
  - 'app/Services/FoundingCircle/**'
---

# Founding Circle

## Heritage No.001 payment inscribes the register
Only Product::FOUNDING_SLUG (heritage-no-001) creates Founding Circle membership. Successful payment writes founding_circle_register for the edition number the buyer picked, visibility private, with no FoundingCircleClaim. A refund deletes that row and drops the role so the number is Available again. The public ledger is always 100 presenter rows: full name, first name plus initial, Privélid, Niet te koop, or Beschikbaar. Names come from the live account, not the historical name column. Do not restore the racket-registration or admin claims screens.

## Return requests mark the piece Returning
When a customer requests a return for a Heritage racket, FoundingCircleRegistrar::markReturningForOrder sets edition_pieces.status to Returning while the register row stays. The public ledger shows Gereserveerd for that number. Full or partial refund that frees the racket still calls releaseForOrder (Available + name gone). Declined or cancelled returns call restoreAllocatedForOrder so the member is Inscribed again.
