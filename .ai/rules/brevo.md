---
paths:
  - app/Services/Brevo/**
  - app/Contracts/BrevoContacts.php
  - app/Jobs/SyncOrderToBrevo.php
---

# Brevo

## Paid orders use list 2, not Heritage Letter
SyncOrderToBrevo upserts buyers onto BREVO_LIST_ORDERS (default 2) with FIRSTNAME, SMS, and NOTE. The contact email is the logged-in `users.email`, not `orders.email`. Never add paid orders to BREVO_LIST_HERITAGE_LETTER or newsletter_subscribers. Skip the HTTP call when the API key or list id is empty.
