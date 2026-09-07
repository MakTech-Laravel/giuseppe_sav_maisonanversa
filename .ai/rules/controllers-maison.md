---
paths:
  - app/Http/Controllers/Maison/VerificationController.php
---

# Controllers Maison

## Verify page resolves holder from order or claim
Public maison.verify shows authenticity for an EditionPiece token. Holder comes from the linked order (name + user) or, when order_id is null, the latest approved Founding Circle claim user. Never expose email; pass Dutch statusLabel keys for t(), and use translatedFormat for allocated/member-since dates. productName stays product->translated('name').
