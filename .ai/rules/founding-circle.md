---
paths:
  - 'app/Services/FoundingCircle/**'
---

# Founding Circle

## Heritage No.001 payment inscribes the register
Only Product::FOUNDING_SLUG (heritage-no-001) creates Founding Circle membership. Successful payment writes founding_circle_register for the edition number the buyer picked, visibility private, with no FoundingCircleClaim. A refund deletes that row and drops the role so the number is Available again. The public ledger is always 100 presenter rows: full name, first name plus initial, Privélid, Niet te koop, or Beschikbaar. Names come from the live account, not the historical name column. Do not restore the racket-registration or admin claims screens.
