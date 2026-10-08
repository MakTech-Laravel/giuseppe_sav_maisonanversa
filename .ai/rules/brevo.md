---
paths:
  - app/Services/Brevo/**
  - app/Contracts/BrevoContacts.php
  - app/Jobs/SyncOrderToBrevo.php
---

# Brevo

## Paid orders use the product list, not Heritage Letter
SyncOrderToBrevo creates the product Brevo list on the first paid order when `products.brevo_list_id` is empty (`POST /v3/contacts/lists`, Dutch product name) and stores the returned id, then upserts the buyer only onto that list with FIRSTNAME, SMS, NOTE, and PRODUCT (the product name in the order locale). Use `BREVO_LIST_FOLDER_ID` when it is set; otherwise find or create a Brevo folder named Products. Later orders reuse the stored id. Do not also add the contact to BREVO_LIST_ORDERS. Deleting an unsold product deletes only that list. Contacts stay. Never add paid orders to BREVO_LIST_HERITAGE_LETTER or newsletter_subscribers, and never put PRODUCT on the Heritage Letter contact. Skip list create, list delete, and the contact call when the API key is empty. PRODUCT must already exist in Brevo as a text contact attribute. The contact email is the logged-in `users.email`, not `orders.email`.
