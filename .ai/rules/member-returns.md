---
paths:
  - 'resources/js/pages/member/returns/**'
---

# Member Returns

## Member returns UX and public entry
Canonical policy URL is /{locale}/returns; nl/retour and fr/retours redirect there. Start a return is login-first (auth modal → member orders). Guest order+email signed URL only when order has no user_id. Wizard steps 1–5; customer may withdraw while status is requested. Damaged reason requires up to 3 photos and sets maison_pays_return_shipping. CTA matrix comes from ReturnEligibility (Cancel before ship; Return items within withdrawal window).
