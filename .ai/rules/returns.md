---
paths:
  - 'app/Services/Returns/**'
---

# Returns

## Customer returns are a separate RMA domain
Use OrderReturn / order_returns (Return is reserved). Do not add return_requested to OrderStatus — order stays paid|processing|shipped|delivered until pre-ship cancel or completed full refund → refunded. RMA statuses live on ReturnStatus. Refund via OrderFulfillment::refund(?amountCents) when return is in_transit (tracking proof) or received; partial needs admin written reason. Founding Circle: request → markReturning (keep register, public shows Reserved/Gereserveerd); decline/cancel → restoreAllocated; full/partial that frees racket → releaseForOrder → Available. Mail R1–R4/A1 are Maison HTML mailables (default Brevo SMTP), not Brevo Transactional API.

## Store Dutch source keys for return event notes
System OrderReturn event notes must store Dutch source strings (e.g. 'Retouraanvraag ingediend.') without __() at write time. Translate on display with t(note) / __() so NL/EN/FR switches stay correct. Free-text admin/customer notes stay as written.
